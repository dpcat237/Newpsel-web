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
     * @param array $dictateItems of unread and read items
     * @param array $unreadItems  of unread items
     * @param int   $limit        limit of dictations to sync
     *
     * @return array
     */
    public function getUnreadItemsApi($labelId, array $dictateItems, array $unreadItems, $limit) {
        $readItems = array();
        $laterItemRepo = $this->doctrine->getRepository('NPSCoreBundle:LaterItem');
        $unreadIds = ArrayHelper::getIdsFromArray($unreadItems, 'api_id');
        $toFilterIds = $this->getDictationsIdsToFilter($dictateItems, $unreadItems);

        $laterItems = $laterItemRepo->getUnreadForApi($labelId, $toFilterIds, $limit);
        //filters and add content
        if (count($unreadIds)) {
            $readItems = $laterItemRepo->getReadDictations($unreadIds);
        }

        $laterItems = $this->addCompleteContent($laterItems);
        $laterItems = $this->removeShortContent($laterItems);
        if (count($readItems)) {
            $laterItems = $this->addReadItems($laterItems, $readItems);
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
            $text = strip_tags($laterItem['content']);
            if (strlen($text) > 1000) {
                $laterItem['text'] = $text;
                $collection[] = $laterItem;
            }
        }

        return $collection;
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
     * Get unread and read items from array
     *
     * @param array $dictateItems
     * @param array $unreadItems
     *
     * @return array
     */
    private function getDictationsIdsToFilter(array $dictateItems, array $unreadItems)
    {
        list($hasErrorItems, $noErrorItems) = ArrayHelper::separateBooleanArray($dictateItems, 'has_tts_error');
        $hasErrorIds = ArrayHelper::getIdsFromArray($hasErrorItems, 'api_id');

        if (!count($unreadItems)) {
            return array();
        }
        if (!is_array($hasErrorIds) || !count($hasErrorIds)) {
            $unreadIds = ArrayHelper::getIdsFromArray($unreadItems, 'api_id');

            return $unreadIds;
        }

        $toFilterIds = array();
        foreach ($unreadItems as $key => $item) {
            if (!in_array($item['api_id'], $hasErrorIds)) {
                $toFilterIds[] = $item['api_id'];
            }
        }

        return $toFilterIds;
    }
}