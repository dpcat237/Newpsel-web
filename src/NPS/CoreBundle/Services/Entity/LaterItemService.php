<?php
namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\CoreBundle\Entity\Item;
use NPS\CoreBundle\Entity\Later;
use NPS\CoreBundle\Entity\UserItem;
use NPS\CoreBundle\Helper\ArrayHelper;
use Predis\Client;
use NPS\CoreBundle\Entity\LaterItem,
    NPS\CoreBundle\Entity\User,
    NPS\CoreBundle\Entity\UserFeed;
use NPS\CoreBundle\Services\CrawlerManager;

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
     * @var CrawlerManager
     */
    private $crawler;

    /**
     * @var Doctrine
     */
    private $doctrine;

    /**
     * @var Entity Manager
     */
    private $entityManager;

    /**
     * @var boolean
     */
    private $import = false;

    /**
     * @var UserItemService
     */
    private $userItem;


    /**
     * @param Registry        $doctrine Doctrine Registry
     * @param Client          $cache    Client
     * @param UserItemService $userItem UserItemService
     * @param CrawlerManager  $crawler CrawlerManager
     */
    public function __construct(Registry $doctrine, Client $cache, UserItemService $userItem, CrawlerManager $crawler)
    {
        $this->cache = $cache;
        $this->crawler = $crawler;
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

        //"+5" extra to don't do many loops for few items
        $laterItems = $this->getUnreadForApiRecursive($laterItemRepo, $labelId, $unreadIds, 0, $limit+5, $totalUnread);
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
     * @param int                 $begin         first item for query
     * @param int                 $limit         quantity of items required by API
     * @param int                 $totalUnread   total unread items of later
     *
     * @return array
     */
    private function getUnreadForApiRecursive($laterItemRepo, $labelId, $unreadIds, $begin, $limit, $totalUnread)
    {
        $laterItems = $laterItemRepo->getUnreadForApiByLabel($labelId, $begin, $limit);
        if (count($unreadIds)) {
            $laterItems = ArrayHelper::filterUnreadItemsIds($laterItems, $unreadIds);
        }
        $laterItems = $this->addCompleteContent($laterItems);
        $laterItems = $this->removeShortContent($laterItems);

        $unreadCount = count($laterItems);
        $begin = $begin + $limit;
        if ($unreadCount >= $limit || ($begin + 1) >= $totalUnread || $limit < 5) { //added 5 just in case to don't do a lot of loops for few items
            return $laterItems;
        }

        $limit -= $unreadCount;
        if (($begin + $limit) > $totalUnread) {
            $limit = $totalUnread - $begin;
        }
        $newLaterItems = $this->getUnreadForApiRecursive($laterItemRepo, $labelId, $unreadIds, $begin, $limit, $totalUnread);
        $laterItems = array_merge($laterItems, $newLaterItems);

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
        //remove html tags
        $text = strip_tags($text);
        $pattern = "#[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~\#?&//=]*)?#si";
        $text = preg_replace($pattern, "", $text);

        //remove long space
        $text = trim(preg_replace('/\s+/', ' ', $text));

        //remove &...; chars
        $text = preg_replace("/&#?[a-z0-9]{2,8};/i","",$text);


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
        foreach ($readItems as $readItem) {
            $item = array(
                'api_id' => $readItem['api_id'],
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
     * @param User    $user
     * @param int     $labelId
     * @param string  $title
     * @param string  $url
     * @param boolean $shared
     */
    public function addPageToLater(User $user, $labelId, $title, $url, $shared = false)
    {
        $exists = $this->existsItem($user->getId(), $url, $labelId);
        if ($exists instanceof LaterItem) {
            $laterItem = $exists;
            if ($labelId == $laterItem->getLaterId()) {
                $laterItem->setUnread(true);
                $this->entityManager->persist($laterItem);
                $this->entityManager->flush();
            } else {
                $this->addLaterItem($laterItem->getUserItem(), $labelId);
            }

            return;
        }

        $item = $exists;
        if (!$item instanceof Item) {
            $item = $this->createItem($title, $url);
        }
        $userItem = $this->createUserItem($user, $item, $shared);
        $this->addLaterItem($userItem, $labelId);
    }

    /**
     * Import later item from Pocket
     *
     * @param User   $user
     * @param int    $labelId
     * @param string $title
     * @param string $url
     * @param int    $dateAdd
     * @param int    $isArticle
     */
    public function importItemFromPocket(User $user, $labelId, $title, $url, $dateAdd, $isArticle = 0)
    {
        $this->import = false;
        $exists = $this->existsItem($user->getId(), $url, $labelId);
        if ($exists == true) {
            return;
        }

        $item = $exists;
        if (!$item instanceof Item) {
            $content = ($isArticle)? $this->crawler->getFullArticle($url) : '';
            $item = $this->createItem($title, $url, $content);
        }
        $userItem = $this->createUserItem($user, $item, true);
        $this->addLaterItem($userItem, $labelId, $dateAdd);
    }

    /**
     * Check if item already exists
     *
     * @param int $userId
     * @param string $url
     *
     * @return bool|Item|LaterItem
     */
    private function existsItem($userId, $url) {
        $itemRepo = $this->doctrine->getRepository('NPSCoreBundle:Item');
        $item = $itemRepo->findOneByLink($url);
        if (!$item instanceof Item) {
            return false;
        }

        $laterItemRepo = $this->doctrine->getRepository('NPSCoreBundle:LaterItem');
        $laterItem = $laterItemRepo->getByItemId($userId, $item->getId());
        if ($laterItem instanceof LaterItem && $this->import) {
            return true;
        }

        if ($laterItem instanceof LaterItem) {
            return $laterItem;
        }

        return $item;
    }

    /**
     * Create item from title and url
     *
     * @param string $pageTitle
     * @param string $pageUrl
     * @param string $content
     *
     * @return Item
     */
    private function createItem($pageTitle, $pageUrl, $content = '')
    {
        $item = new Item();
        $item->setContentHash(sha1($pageUrl));
        $item->setLink($pageUrl);
        $item->setTitle($pageTitle);
        $content = (strlen($content) > 20)? $content : $pageTitle.'...';
        $item->setContent($content);
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item;
    }

    /**
     * Create user item
     *
     * @param User $user
     * @param Item $item
     * @param bool $shared
     *
     * @return UserItem
     */
    private function createUserItem(User $user, Item $item, $shared = false)
    {
        $userItem = new UserItem();
        $userItem->setUser($user);
        $userItem->setItem($item);
        $userItem->setShared($shared);
        $this->entityManager->persist($userItem);
        $this->entityManager->flush();

        return $userItem;
    }


    /**
     * Add new later item
     *
     * @param UserItem $userItem
     * @param int      $labelId
     * @param int      $dateAdd
     *
     */
    public function addLaterItem(UserItem $userItem, $labelId, $dateAdd = 0)
    {
        $laterRepo = $this->doctrine->getRepository('NPSCoreBundle:Later');
        $later = $laterRepo->find($labelId);
        if (!$later instanceof Later) {
            return;
        }

        $laterItem = new LaterItem();
        $laterItem->setLater($later);
        $laterItem->setUserItem($userItem);
        if ($dateAdd) {
            $laterItem->setDateAdd($dateAdd);
        }
        $this->entityManager->persist($laterItem);
        $this->entityManager->flush();
    }
}