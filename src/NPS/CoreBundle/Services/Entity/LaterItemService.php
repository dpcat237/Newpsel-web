<?php

namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Dpcat237\CrawlerBundle\Library\Crawler;
use NPS\CoreBundle\Constant\EntityConstants;
use NPS\CoreBundle\Constant\RedisConstants;
use NPS\CoreBundle\Entity\Item;
use NPS\CoreBundle\Entity\Later;
use NPS\CoreBundle\Entity\UserItem;
use NPS\CoreBundle\Helper\ArrayHelper;
use NPS\CoreBundle\Repository\LaterItemRepository;
use Predis\Client;
use NPS\CoreBundle\Entity\LaterItem;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Entity\UserFeed;
use NPS\CoreBundle\Services\QueueLauncherService;

/**
 * Class LaterItemService
 *
 * @package NPS\CoreBundle\Services\Entity
 */
class LaterItemService
{
    /** @var Client */
    protected $cache;

    /** @var Crawler */
    protected $crawler;

    /** @var EntityManager */
    protected $entityManager;

    /** @var boolean */
    protected $import = false;

    /** @var UserItemService */
    protected $userItem;

    /** @var QueueLauncherService */
    protected $queue;

    /** @var LaterItemRepository */
    protected $laterItemRepository;

    /**
     * LaterItemService constructor.
     *
     * @param EntityManager        $entityManager
     * @param Client               $cache
     * @param UserItemService      $userItem
     * @param Crawler              $crawler
     * @param QueueLauncherService $queue
     */
    public function __construct(EntityManager $entityManager, Client $cache, UserItemService $userItem, Crawler $crawler, QueueLauncherService $queue)
    {
        $this->cache               = $cache;
        $this->crawler             = $crawler;
        $this->entityManager       = $entityManager;
        $this->userItem            = $userItem;
        $this->queue               = $queue;
        $this->laterItemRepository = $entityManager->getRepository(LaterItem::class);
    }

    /**
     * Get page title for later view
     *
     * @param LaterItem $laterItem
     * @param User      $user
     *
     * @return string
     */
    public function getViewTitle(LaterItem $laterItem, User $user)
    {
        $userFeedRepo  = $this->entityManager->getRepository(UserFeed::class);
        $whereUserFeed = array(
            'feed' => $laterItem->getUserItem()->getItem()->getFeedId(),
            'user' => $user->getId()
        );
        $userFeed      = $userFeedRepo->findOneBy($whereUserFeed);
        $title         = ($userFeed instanceof UserFeed) ? $userFeed->getTitle() : $laterItem->getLater()->getName();

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

        if ($content = $this->cache->get('crawledItem_' . $item->getId())) {
            $item->setContent($content);
        }

        return $item;
    }

    /**
     * Make later item read
     *
     * @param LaterItem $laterItem
     * @param int       $state
     */
    public function makeLaterRead(LaterItem $laterItem, $state = 2)
    {
        if ($state == EntityConstants::STATUS_READ) {
            $laterItem->setUnread(false);
        } else {
            $laterItem->setUnread(true);
        }
        $this->entityManager->persist($laterItem);
        $this->entityManager->flush();
    }

    /**
     * Get unread later items for API
     *
     * @param int   $labelId
     * @param array $unreadItems of unread items
     * @param int   $limit       limit of dictations to sync
     *
     * @return array
     */
    public function getUnreadItemsApi($labelId, array $unreadItems, $limit)
    {
        $readItems   = array();
        $totalUnread = $this->laterItemRepository->totalLaterUnreadItems($labelId);
        $unreadIds   = ArrayHelper::getIdsFromArray($unreadItems, 'api_id');

        //"+5" extra to don't do many loops for few items
        $laterItems = $this->getUnreadForApiRecursive($labelId, $unreadIds, 0, $limit + 5, $totalUnread);
        if (count($unreadIds)) {
            $readItems = $this->laterItemRepository->getReadItems($unreadIds);
        }
        if (count($readItems)) {
            $laterItems = $this->addReadItems($laterItems, $readItems);
        }

        return $laterItems;
    }

    /**
     * Get unread items for API
     *
     * @param int   $labelId     later id
     * @param array $unreadIds   ids of unread and added to sync items
     * @param int   $begin       first item for query
     * @param int   $limit       quantity of items required by API
     * @param int   $totalUnread total unread items of later
     *
     * @return array
     */
    private function getUnreadForApiRecursive($labelId, $unreadIds, $begin, $limit, $totalUnread)
    {
        $laterItems = $this->laterItemRepository->getUnreadForApiByLabel($labelId, $begin, $limit);
        if (count($unreadIds)) {
            $laterItems = ArrayHelper::filterUnreadItemsIds($laterItems, $unreadIds);
        }
        $laterItems = $this->addCompleteContent($laterItems);
        $laterItems = $this->removeShortContent($laterItems);

        $unreadCount = count($laterItems);
        $begin       = $begin + $limit;
        if ($unreadCount >= $limit || ($begin + 1) >= $totalUnread || $limit < 5) { //added 5 just in case to don't do a lot of loops for few items
            return $laterItems;
        }

        $limit -= $unreadCount;
        if (($begin + $limit) > $totalUnread) {
            $limit = $totalUnread - $begin;
        }
        $newLaterItems = $this->getUnreadForApiRecursive($labelId, $unreadIds, $begin, $limit, $totalUnread);
        $laterItems    = array_merge($laterItems, $newLaterItems);

        return $laterItems;
    }

    /**
     * Get complete content for items and filtering items without complete content
     *
     * @param array $collection
     *
     * @return mixed
     */
    private function addCompleteContent(array $collection)
    {
        $laterItems = array();
        foreach ($collection as $key => $laterItem) {
            if ($content = $this->cache->get('crawledItem_' . $laterItem['item_id'])) {
                $laterItem['content'] = $content;
                $laterItems[$key]     = $laterItem;
            }
        }

        return $laterItems;
    }

    /**
     * Remove items with short content and adjust language
     *
     * @param $laterItems
     *
     * @return array
     */
    private function removeShortContent(array $laterItems)
    {
        if (count($laterItems) < 1) {
            return array();
        }

        $collection = array();
        foreach ($laterItems as $laterItem) {
            $text = $this->removeUnreadContentFromText($laterItem['content']);
            if (strlen($text) < 250 || (strlen($laterItem['language']) != 2 && strlen($laterItem['item_language']) != 2)) {
                continue;
            }

            $laterItem['text']     = $text;
            $laterItem['language'] = (strlen($laterItem['language']) == 2) ? $laterItem['language'] : $laterItem['item_language'];
            $collection[]          = $laterItem;
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
        $text    = strip_tags($text);
        $pattern = "#[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~\#?&//=]*)?#si";
        $text    = preg_replace($pattern, "", $text);

        //remove long space
        $text = trim(preg_replace('/\s+/', ' ', $text));

        //remove &...; chars
        $text = preg_replace("/&#?[a-z0-9]{2,8};/i", "", $text);


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
    private function addReadItems($laterItems, $readItems)
    {
        foreach ($readItems as $readItem) {
            $item         = array(
                'api_id'    => $readItem['api_id'],
                'item_id'   => 0,
                'feed_id'   => 0,
                'tag_id'  => 0,
                'is_unread' => false,
                'date_add'  => 0,
                'language'  => "",
                'link'      => "",
                'title'     => "",
                'content'   => "",
                'text'      => ""
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
     * Import later item
     *
     * @param User   $user
     * @param int    $labelId
     * @param string $title
     * @param string $url
     * @param int    $dateAdd
     * @param int    $isArticle
     */
    public function importItem(User $user, $labelId, $title, $url, $dateAdd, $isArticle = 0)
    {
        $this->import = false;
        $exists       = $this->existsItem($user->getId(), $url, $labelId);
        if ($exists == true) {
            return;
        }

        $item = $exists;
        if (!$item instanceof Item) {
            $content = ($isArticle) ? $this->crawler->getFullArticle($url) : '';
            $item    = $this->createItem($title, $url, $content, $dateAdd);
        }
        $userItem = $this->createUserItem($user, $item, true);
        $this->addLaterItem($userItem, $labelId);
    }

    /**
     * Check if item already exists
     *
     * @param int    $userId
     * @param string $url
     *
     * @return bool|Item|LaterItem
     */
    private function existsItem($userId, $url)
    {
        $itemRepo = $this->entityManager->getRepository(Item::class);
        $item     = $itemRepo->findOneByLink($url);
        if (!$item instanceof Item) {
            return false;
        }

        $laterItem = $this->laterItemRepository->getByItemId($userId, $item->getId());
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
     * @param int    $dateAdd
     *
     * @return Item
     */
    private function createItem($pageTitle, $pageUrl, $content = '', $dateAdd = 0)
    {
        $item = new Item();
        $item->setContentHash(sha1($pageUrl));
        $item->setLink($pageUrl);
        $item->setTitle($pageTitle);
        $content = (strlen($content) > 20) ? $content : $pageTitle . '...';
        $item->setContent($content);
        if ($dateAdd) {
            $item->setDateAdd($dateAdd);
        }
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
     */
    public function addLaterItem(UserItem $userItem, $labelId)
    {
        $laterRepo = $this->entityManager->getRepository(Later::class);
        $later     = $laterRepo->find($labelId);
        if (!$later instanceof Later) {
            return;
        }

        $laterItem = new LaterItem();
        $laterItem->setLater($later);
        $laterItem->setUserItem($userItem);
        $this->entityManager->persist($laterItem);
        $this->entityManager->flush();
    }

    /**
     * Add new later item. If it exists set Unread to true
     *
     * @param UserItem $userItem
     * @param Later    $later
     */
    public function addLaterItemCheck(UserItem $userItem, Later $later)
    {
        $laterItem = $this->laterItemRepository->laterExists($later->getId(), $userItem->getId());
        if ($laterItem instanceof LaterItem) {
            $laterItem->setUnread(true);
            $this->entityManager->persist($laterItem);
            $this->entityManager->flush();

            return;
        }

        $laterItem = new LaterItem();
        $laterItem->setLater($later);
        $laterItem->setUserItem($userItem);
        $this->entityManager->persist($laterItem);
        $this->entityManager->flush();
    }

    /**
     * Save items to cache and add to query import process
     *
     * @param int   $userId
     * @param int   $labelId
     * @param array $itemsCollections
     */
    public function prepareToImport($userId, $labelId, $itemsCollections)
    {
        $part = 1;
        foreach ($itemsCollections as $items) {
            if (count($items) < 1) {
                continue;
            }

            $redisKey = RedisConstants::IMPORT_LATER_ITEMS . '_' . $userId . '_' . $labelId . '_' . time() . '_' . $part;
            $jsonData = json_encode($items);
            $this->cache->setex($redisKey, 604800, $jsonData); //7 days life
            $this->queue->executeImportItems($redisKey);
            $part++;
        }
    }

    /**
     * Add later items for specific user
     *
     * @param integer $userId
     * @param array   $items
     */
    public function syncLaterItems($userId, $items)
    {
        $userItemRepo = $this->entityManager->getRepository(UserItem::class);
        foreach ($items as $itemData) {
            $itemId   = $itemData['item_id'];
            $labelId  = $itemData['tag_id'];
            $userItem = $userItemRepo->hasItemById($itemId);
            if (!$userItem instanceof UserItem) {
                continue;
            }

            $laterItem = $this->laterItemRepository->laterExists($labelId, $userItem->getId());
            if ($laterItem instanceof LaterItem) {
                $laterItem->setUnread(true);
                $this->entityManager->persist($laterItem);

                continue;
            }

            $this->addLaterItem($userItem, $labelId);
        }
        $this->entityManager->flush();
    }
}
