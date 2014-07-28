<?php
namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\CoreBundle\Entity\Item;
use NPS\CoreBundle\Entity\UserItem;
use NPS\CoreBundle\Helper\ArrayHelper;
use Predis\Client;
use NPS\CoreBundle\Entity\LaterItem,
    NPS\CoreBundle\Entity\User,
    NPS\CoreBundle\Entity\UserFeed;

/**
 * LaterItemService
 */
class LaterItemService
{
    /**
     * @var Client
     */
    private $cache;

    /**
     * @var Doctrine
     */
    private $doctrine;

    /**
     * @var Entity Manager
     */
    private $entityManager;

    /**
     * @var UserItemService
     */
    private $userItem;


    /**
     * @param Registry        $doctrine Doctrine Registry
     * @param Client          $cache    Client
     * @param UserItemService $userItem UserItemService
     */
    public function __construct(Registry $doctrine, Client $cache, UserItemService $userItem)
    {
        $this->cache = $cache;
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->userItem = $userItem;
    }

    /**
     * Get page title for later view
     *
     * @param LaterItem $laterItem
     * @param User $user
     *
     * @return string
     */
    public function getViewTitle(LaterItem $laterItem, User $user)
    {
        $userFeedRepo = $this->doctrine->getRepository('NPSCoreBundle:UserFeed');
        $whereUserFeed = array(
            'feed' => $laterItem->getUserItem()->getItem()->getFeedId(),
            'user' => $user->getId()
        );
        $userFeed = $userFeedRepo->findOneBy($whereUserFeed);
        $title = ($userFeed instanceof UserFeed)? $userFeed->getTitle() : $laterItem->getLater()->getName();

        return $title;
    }

    /**
     * Get Item and make read laterItem and Item. If are get complete content from cache
     *
     * @param LaterItem $laterItem
     *
     * @return Item
     */
    public function readItem(LaterItem $laterItem)
    {
        $item = $laterItem->getUserItem()->getItem();
        $user = $laterItem->getUserItem()->getUser();
        $this->makeLaterRead($laterItem);
        $this->userItem->changeStatus($user, $item, "isUnread", "setUnread", 2);

        if ($content = $this->cache->get('crawledItem_'.$item->getId())) {
            $item->setContent($content);
        }

        return $item;
    }

    /**
     * Make later item read
     * @param LaterItem $laterItem
     */
    public function makeLaterRead(LaterItem $laterItem)
    {
        $laterItem->setUnread(false);
        $this->entityManager->persist($laterItem);
        $this->entityManager->flush();
    }

    /**
     * Get unread later items for API
     *
     * @param int   $labelId
     * @param array $unreadItems  of unread items
     * @param int   $limit        limit of dictations to sync
     *
     * @return array
     */
    public function getUnreadItemsApi($labelId, array $unreadItems, $limit) {
        $readItems = array();
        $laterItemRepo = $this->doctrine->getRepository('NPSCoreBundle:LaterItem');
        $totalUnread = $laterItemRepo->totalLaterUnreadItems($labelId);
        $unreadIds = ArrayHelper::getIdsFromArray($unreadItems, 'api_id');

        $laterItems = $this->getUnreadForApiRecursive($laterItemRepo, $labelId, $unreadIds, $limit, $totalUnread);
        if (count($unreadIds)) {
            $readItems = $laterItemRepo->getReadDictations($unreadIds);
        }
        if (count($readItems)) {
            $laterItems = $this->addReadItems($laterItems, $readItems);
        }

        return $laterItems;
    }

    /**
     * Get unread items for API
     *
     * @param LaterItemRepository $laterItemRepo LaterItem
     * @param int                 $labelId       later id
     * @param array               $unreadIds     ids of unread and added to sync items
     * @param int                 $limit         quantity of items required by API
     * @param int                 $totalUnread   total unread items of later
     *
     * @return array
     */
    private function getUnreadForApiRecursive($laterItemRepo, $labelId, $unreadIds, $limit, $totalUnread)
    {
        $laterItems = $laterItemRepo->getUnreadForApi($labelId, $unreadIds, $limit);
        $newUnreadIds = ArrayHelper::getIdsFromArray($laterItems, 'api_id');
        $laterItems = $this->addCompleteContent($laterItems);
        $laterItems = $this->removeShortContent($laterItems);

        if (count($laterItems) >= $limit) {
            return $laterItems;
        }

        $unreadIds = array_merge($unreadIds, $newUnreadIds);
        if (count($unreadIds) < $totalUnread) {
            //get more unread items
            $limit = $limit - count($laterItems);
            $newLaterItems = $this->getUnreadForApiRecursive($laterItemRepo, $labelId, $unreadIds, $limit, $totalUnread);
            $laterItems = array_merge($laterItems, $newLaterItems);
        }

        return $laterItems;
    }

    /**
     * Add complete content for later items, if exists
     *
     * @param array $laterItems
     *
     * @return mixed
     */
    private function addCompleteContent(array $laterItems)
    {
        foreach ($laterItems as $key => $laterItem) {
            if ($content = $this->cache->get('crawledItem_'.$laterItem['item_id'])) {
                $laterItems[$key]['content'] = $content;
            }
        }

        return $laterItems;
    }

    /**
     * Remove items with short content
     *
     * @param $laterItems
     *
     * @return array
     */
    private function removeShortContent(array $laterItems) {
        $collection = array();
        foreach ($laterItems as $laterItem) {
            $text = $this->removeUnreadContentFromText($laterItem['content']);
            if (strlen($text) < 1000) {
                continue;
            }

            $laterItem['text'] = $text;
            $collection[] = $laterItem;
            if (strlen($laterItem['item_language']) == 2) {
                $laterItem['language'] = $laterItem['item_language'];
            }
        }

        return $collection;
    }

    /**
     * Remove from text to be readable:
     * - html tags
     * - urls
     *
     * @param string $text
     *
     * @return string
     */
    private function removeUnreadContentFromText($text)
    {
        $text = strip_tags($text);
        $pattern = "#[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~\#?&//=]*)?#si";
        $text = preg_replace($pattern, "", $text);

        return $text;
    }

    /**
     * Add read items which came as unread from api
     *
     * @param array $laterItems
     * @param array $readItems
     *
     * @return array
     */
    private function addReadItems($laterItems, $readItems) {
        foreach ($readItems as $unreadItem) {
            $item = array(
                'api_id' => $unreadItem['api_id'],
                'item_id' => 0,
                'feed_id' => 0,
                'later_id' => 0,
                'is_unread' => false,
                'date_add' => 0,
                'language' => "",
                'link' => "",
                'title' => "",
                'content' => "",
                'text' => ""
            );
            $laterItems[] = $item;
        }

        return $laterItems;
    }

    /**
     * Add page / item to selected label or if exists set as unread
     *
     * @param User   $user
     * @param id     $labelId
     * @param string $pageTitle
     * @param string $pageUrl
     */
    public function addPageToLater(User $user, $labelId, $pageTitle, $pageUrl)
    {
        $laterItemRepo = $this->doctrine->getRepository('NPSCoreBundle:LaterItem');
        $laterItem = $laterItemRepo->checkExistsLaterItemUrl($user->getId(), $labelId, $pageUrl);
        if (!$laterItem instanceof LaterItem) {
            $item = new Item();
            $item->setContentHash(sha1($pageUrl));
            $item->setLink($pageUrl);
            $item->setTitle($pageTitle);
            $item->setContent($pageTitle.'...');
            $this->entityManager->persist($item);
            $this->entityManager->flush();

            $userItem = new UserItem();
            $userItem->setItem($item);
            $userItem->setUser($user);
            $this->entityManager->persist($userItem);
            $this->entityManager->flush();

            $laterRepo = $this->doctrine->getRepository('NPSCoreBundle:Later');
            $later = $laterRepo->find($labelId);
            $laterItem = new LaterItem();
            $laterItem->setLater($later);
            $laterItem->setUserItem($userItem);
            $this->entityManager->persist($laterItem);
            $this->entityManager->flush();
        } elseif (!$laterItem->isUnread()) {
            $laterItem->setUnread(true);
            $this->entityManager->persist($laterItem);
            $this->entityManager->flush();
        }
    }

    /**
     * Add new later item
     *
     * @param UserItem $userItem
     * @param int      $labelId
     */
    public function addLaterItem(UserItem $userItem, $labelId)
    {
        $laterRepo = $this->doctrine->getRepository('NPSCoreBundle:Later');

        $laterItem = new LaterItem();
        $laterItem->setLater($laterRepo->find($labelId));
        $laterItem->setUserItem($userItem);
        $this->entityManager->persist($laterItem);
    }
}