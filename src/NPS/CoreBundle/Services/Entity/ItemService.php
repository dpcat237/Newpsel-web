<?php
namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\CoreBundle\Services\FilteringManager;
use Predis\Client;
use SimplePie_Item;
use HTMLPurifier,
    HTMLPurifier_Config;
use NPS\CoreBundle\Entity\Feed,
    NPS\CoreBundle\Entity\Item,
    NPS\CoreBundle\Entity\User,
    NPS\CoreBundle\Entity\UserItem;

/**
 * ItemService
 */
class ItemService
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
     * @var FilteringManager
     */
    private $filter;

    /**
     * @var LaterItemService
     */
    private $laterItem;

    /**
     * @var HTMLPurifier
     */
    private $purifier;

    /**
     * @param Registry         $doctrine  Doctrine Registry
     * @param Client           $cache     Client
     * @param FilteringManager $filter    FilteringManager
     * @param LaterItemService $laterItem LaterItemService
     */
    public function __construct(Registry $doctrine, Client $cache, FilteringManager $filter, LaterItemService $laterItem)
    {
        $this->cache = $cache;
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->filter = $filter;
        $this->laterItem = $laterItem;

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
        $itemRepo = $this->doctrine->getRepository('NPSCoreBundle:Item');
        $userItemRepo = $this->doctrine->getRepository('NPSCoreBundle:UserItem');
        $items = $itemRepo->getLast($feed->getId());

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
     * @param Feed $feed
     */
    public function addNewItem(SimplePie_Item $itemData, Feed $feed)
    {
        $item = new Item();
        $item->setFeed($feed);
        $item->setDateAdd($itemData->get_date('U'));
        $item->setContentHash(sha1($itemData->get_content()));
        $item->setLink($itemData->get_link());
        $title = $this->purifier->purify($itemData->get_title());
        $item->setTitle(html_entity_decode($title, ENT_QUOTES, 'UTF-8'));
        $item->setContent($this->purifier->purify($itemData->get_content()));

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        $linkHash = "item_url_hash_".sha1($itemData->get_link());
        $ttl = 86400;
        $this->cache->setex($linkHash, $ttl, $item->getId());

        $userFeedRepo = $this->doctrine->getRepository('NPSCoreBundle:UserFeed');
        $whereUsersFeeds = array(
            'feed' => $feed->getId(),
            'deleted' => false
        );
        $usersFeeds = $userFeedRepo->findBy($whereUsersFeeds);

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
            $laterId = $this->filter->checkUserFeedFilter($feedUser->getUser()->getId(), $feedUser->getFeed()->getId(), 'to.label');
            $unread =($laterId)? false : true;
            $userItem = $this->addUserItem($feedUser->getUser(), $item, $unread);
            if ($laterId) {
                $this->laterItem->addLaterItem($userItem, $laterId);
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
     * Check if exist item by url
     * @param $link
     *
     * @return mixed
     */
    public function checkExistByLink($link) {
        $linkHash = "item_url_hash_".sha1($link);
        $itemId = $this->cache->get($linkHash);
        if ($itemId) {
            $itemRepo = $this->doctrine->getRepository('NPSCoreBundle:Item');

            return $itemRepo->find($itemId);
        } else {
            return null;
        }
    }

    /**
     * Update content of exists item
     *
     * @param Item           $item
     * @param SimplePie_Item $itemData
     */
    public function updateItemContent(Item $item , SimplePie_Item $itemData)
    {
        $item->setTitle($itemData->get_title());
        $item->setContent($itemData->get_content());

        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }
}