<?php

namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use NPS\CoreBundle\Constant\RedisConstants;
use NPS\CoreBundle\Entity\Filter;
use NPS\CoreBundle\Repository\ItemRepository;
use NPS\CoreBundle\Services\FilteringManager;
use NPS\CoreBundle\Services\QueueLauncherService;
use Predis\Client;
use SimplePie_Item;
use HTMLPurifier;
use HTMLPurifier_Config;
use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Entity\Item;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Entity\UserItem;
use NPS\CoreBundle\Entity\UserFeed;

/**
 * Class ItemService
 *
 * @package NPS\CoreBundle\Services\Entity
 */
class ItemService
{
    /** @var Client */
    protected $cache;

    /** @var EntityManager */
    protected $entityManager;

    /** @var FilteringManager */
    protected $filter;

    /** @var ItemRepository */
    protected $itemRepository;

    /** @var LaterItemService */
    protected $laterItem;

    /** @var HTMLPurifier */
    protected $purifier;

    /** @var QueueLauncherService */
    protected $queueLauncher;

    /**
     * ItemService constructor.
     *
     * @param EntityManager        $entityManager
     * @param Client               $cache
     * @param FilteringManager     $filter
     * @param LaterItemService     $laterItem
     * @param QueueLauncherService $queueLauncher
     */
    public function __construct(
        EntityManager $entityManager,
        Client $cache,
        FilteringManager $filter,
        LaterItemService $laterItem,
        QueueLauncherService $queueLauncher
    )
    {
        $this->cache          = $cache;
        $this->entityManager  = $entityManager;
        $this->filter         = $filter;
        $this->laterItem      = $laterItem;
        $this->queueLauncher  = $queueLauncher;
        $this->itemRepository = $entityManager->getRepository(Item::class);

        if (empty($this->purifier)) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.SafeObject', true);
            $config->set('Output.FlashCompat', true);
            $this->purifier = new HTMLPurifier($config);
        }
    }

    /**
     * Add last items to new user
     *
     * @param User $user
     * @param Feed $feed
     */
    public function addLastItemsNewUser(User $user, Feed $feed)
    {
        $userItemRepo = $this->entityManager->getRepository(UserItem::class);
        $items        = $this->itemRepository->getLast($feed->getId());

        foreach ($items as $item) {
            if (!$userItemRepo->hasItem($user->getId(), $item->getId())) {
                $this->addUserItem($user, $item);
            }
        }
        $this->entityManager->flush();
    }

    /**
     * Add new item
     *
     * @param SimplePie_Item $itemData
     * @param Feed           $feed
     */
    public function addNewItem(SimplePie_Item $itemData, Feed $feed)
    {
        $item = new Item();
        $item->setFeed($feed);
        $item->setDateAdd($itemData->get_date('U'));
        $item->setContentHash(sha1($itemData->get_content()));
        $item->setLink($itemData->get_link());
        $title = $this->purifier->purify($itemData->get_title());
        $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
        $item->setTitle($title);
        $item->setContent($this->purifier->purify($itemData->get_content()));

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        //save temporary hash of url
        $linkHash = RedisConstants::ITEM_URL_HASH . "_" . sha1($itemData->get_link());
        $ttl      = 259200; // 3 days
        $this->cache->setex($linkHash, $ttl, $item->getId());
        //save temporary hash of title
        $titleHash = RedisConstants::ITEM_TITLE_HASH . "_" . sha1($title);
        $this->cache->setex($titleHash, $ttl, $item->getId());

        $userFeedRepo    = $this->entityManager->getRepository(UserFeed::class);
        $whereUsersFeeds = [
            'feed'    => $feed->getId(),
            'deleted' => false
        ];
        $usersFeeds      = $userFeedRepo->findBy($whereUsersFeeds);
        $this->addItemToSubscribers($item, $usersFeeds);
    }

    /**
     * Add item to subscribers
     *
     * @param Item  $item      Item
     * @param array $feedUsers feed users
     */
    private function addItemToSubscribers($item, $feedUsers)
    {
        foreach ($feedUsers as $feedUser) {
            $laterId  = $this->filter->checkUserFeedFilter($feedUser->getUser()->getId(), $feedUser->getFeed()->getId(), Filter::FILTER_FEED_TO_TAG);
            $unread   = ($laterId) ? false : true;
            $userItem = $this->addUserItem($feedUser->getUser(), $item, $unread);
            if ($laterId) {
                $this->laterItem->addLaterItem($userItem, $laterId);
                $this->queueLauncher->executeCrawling($feedUser->getUser()->getId());
            }
        }
        $this->entityManager->flush();
    }

    /**
     * Add new user item
     *
     * @param User $user
     * @param Item $item
     *
     * @return UserItem
     */
    protected function addLaterItem(User $user, Item $item)
    {
        $userItem = new UserItem();
        $userItem->setUser($user);
        $userItem->setItem($item);

        return $userItem;
    }

    /**
     * Add new user item
     *
     * @param User    $user
     * @param Item    $item
     * @param boolean $unread
     *
     * @return UserItem
     */
    protected function addUserItem(User $user, Item $item, $unread = true)
    {
        $userItem = new UserItem();
        $userItem->setUser($user);
        $userItem->setItem($item);
        $userItem->setUnread($unread);
        $this->entityManager->persist($userItem);

        return $userItem;
    }

    /**
     * Check if item already exists by url or title hash to update his data
     *
     * @param string $link
     * @param string $title
     *
     * @return mixed
     */
    public function checkItemWasUpdated($link, $title)
    {
        $linkHash = RedisConstants::ITEM_URL_HASH . "_" . sha1($link);
        $itemId   = $this->cache->get($linkHash);

        if (!$itemId) {
            $title     = $this->purifier->purify($title);
            $title     = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
            $titleHash = RedisConstants::ITEM_TITLE_HASH . "_" . sha1($title);
            $itemId    = $this->cache->get($titleHash);
        }

        if ($itemId) {
            return $this->itemRepository->find($itemId);
        }

        return null;
    }

    /**
     * Update content of exists item
     *
     * @param Item           $item
     * @param SimplePie_Item $itemData
     */
    public function updateItemContent(Item $item, SimplePie_Item $itemData)
    {
        $item->setTitle($itemData->get_title());
        $item->setContent($itemData->get_content());

        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }
}
