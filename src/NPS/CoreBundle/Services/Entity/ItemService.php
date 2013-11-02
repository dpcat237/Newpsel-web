<?php
namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use \SimplePie_Item;
use HTMLPurifier,
    HTMLPurifier_Config;
use NPS\CoreBundle\Entity\Feed,
    NPS\CoreBundle\Entity\Item,
    NPS\CoreBundle\Entity\LaterItem,
    NPS\CoreBundle\Entity\User,
    NPS\CoreBundle\Entity\UserItem;
use NPS\CoreBundle\Services\CacheService;

/**
 * ItemService
 */
class ItemService
{
    /**
     * @var Redis
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
     * @var HTMLPurifier
     */
    private $purifier;

    /**
     * @param Registry     $doctrine Doctrine Registry
     * @param CacheService $cache    CacheService
     */
    public function __construct(Registry $doctrine, CacheService $cache)
    {
        $this->cache = $cache;
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();

        if (empty($this->purifier)) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.SafeObject', true);
            $config->set('Output.FlashCompat', true);
            $this->purifier = new HTMLPurifier($config);
        }
    }

    /**
     * Add item
     * @param SimplePie_Item $itemData SimplePie_Item
     * @param Feed           $feed     Feed
     */
    public function addItem(SimplePie_Item $itemData, Feed $feed)
    {
        $item = $this->checkExistByLink($itemData->get_link());
        if ($item instanceof Item) {
            $item->setTitle($itemData->get_title());
            $item->setContent($itemData->get_content());

            $this->entityManager->persist($item);
            $this->entityManager->flush();
        } else {
            $item = new Item();
            $item->setFeed($feed);
            $item->setDateAdd($itemData->get_date('U'));
            $item->setContentHash(sha1($itemData->get_content()));
            $item->setLink($itemData->get_link());
            $item->setTitle($this->purifier->purify($itemData->get_title()));
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
    }

    /**
     * Add page / item to selected label or if exists set as unread
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
     * Add shared pages from api
     * @param User $user
     * @param $sharedItems
     */
    public function addSharedItems(User $user, $sharedItems)
    {
        foreach ($sharedItems as $sharedItem) {
            $this->addPageToLater($user, $user->getPreference()->getSharedLaterId(), $sharedItem['title'], $sharedItem['text']);
        }
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
     * Add item to subscribers
     * @param Item  $item
     * @param array $userFeeds
     */
    private function addItemToSubscribers($item, $userFeeds)
    {
        foreach ($userFeeds as $userFeed) {
            $userItem = new UserItem();
            $userItem->setUser($userFeed->getUser());
            $userItem->setItem($item);
            $this->entityManager->persist($userItem);
        }
        $this->entityManager->flush();
    }

    /**
     * Change item status
     *
     * @param User $user
     * @param Item $item
     * @param $statusGet
     * @param $statusSet
     * @param null $change
     *
     * @return boolean set status
     */
    public function changeStatus(User $user, Item $item, $statusGet, $statusSet, $change = null)
    {
        $itemRepo = $this->doctrine->getRepository('NPSCoreBundle:Item');
        $userItem = $itemRepo->hasItem($user->getId(), $item->getId());

        if ($userItem instanceof UserItem) {
            $status = $this->changeUserItemStatus($userItem, $statusGet, $statusSet, $change);

            return $status;
        }

        $userItem = new UserItem();
        $userItem->setUser($user);
        $userItem->setItem($item);
        if ($change == 1) {
            $status = true;
        } else {
            $status = false;
        }

        $userItem->$statusSet($status);
        $this->entityManager->persist($userItem);
        $this->entityManager->flush();

        return $status;
    }

    /**
     * Set new user's item status
     *
     * @param UserItem $userItem  user's item
     * @param string   $statusGet status get method name
     * @param string   $statusSet status set method name
     * @param int      $change    new status
     *
     * @return bool
     */
    public function changeUserItemStatus(UserItem $userItem, $statusGet, $statusSet, $change = null)
    {
        $status = $this->getNewStatus($userItem, $change, $statusGet);
        $userItem->$statusSet($status);
        $this->entityManager->persist($userItem);
        $this->entityManager->flush();

        return $status;
    }

    /**
     * Get new status for existing userItem
     * @param UserItem $userItem
     * @param $change
     * @param $statusGet
     *
     * @return bool
     */
    private function getNewStatus(UserItem $userItem, $change, $statusGet){
        if ($change == 1) {
            $status = true;
        } elseif ($change == 2)  {
            $status = false;
        } else {
            if ($userItem->$statusGet()) { //change current status
                $status = false;
            } else {
                $status = true;
            }
        }

        return $status;
    }
}
