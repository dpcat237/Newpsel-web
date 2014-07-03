<?php
namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
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
     * @var ItemService
     */
    private $item;


    /**
     * @param Registry     $doctrine Doctrine Registry
     * @param Client       $cache    Client
     * @param ItemService  $item     Item service
     */
    public function __construct(Registry $doctrine, Client $cache, ItemService $item)
    {
        $this->cache = $cache;
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->item = $item;
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
        $this->item->changeStatus($user, $item, "isUnread", "setUnread", 2);

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
     * @param array $unreadItems of unread items
     * @param int   $limit       limit of dictations to sync
     *
     * @return array
     */
    public function getUnreadItemsApi($labelId, $unreadItems, $limit) {
        $laterItemRepo = $this->doctrine->getRepository('NPSCoreBundle:LaterItem');
        $laterItems = $laterItemRepo->getUnreadForApi($labelId, $limit);

        //filters and add content
        $laterItems = $this->removeUnreadDictations($laterItems, $unreadItems);
        $laterItems = $this->addCompleteContent($laterItems);
        $laterItems = $this->removeShortContent($laterItems);

        return $laterItems;
    }

    /**
     * Remove items which still unread
     *
     * @param array $laterItems
     * @param array $unreadItems
     *
     * @return mixed
     */
    private function removeUnreadDictations($laterItems, $unreadItems)
    {
        foreach ($unreadItems as $unreadItem) {
            foreach ($laterItems as $key => $laterItem) {
                if ($unreadItem['api_id'] == $laterItem['api_id']) {
                    unset($laterItems[$key]);
                }
            }
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
    private function addCompleteContent($laterItems)
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
    private function removeShortContent($laterItems) {
        $collection = array();
        foreach ($laterItems as $laterItem) {
            $text = strip_tags($laterItem['content']);
            if (strlen($text) > 1000) {
                $laterItem['text'] = $text;
                $collection[] = $laterItem;
            }
        }

        return $collection;
    }

}