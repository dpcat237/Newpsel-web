<?php

namespace NPS\ApiBundle\Services\Entity;

use Doctrine\ORM\EntityManager;
use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Helper\ArrayHelper;
use NPS\CoreBundle\Repository\UserItemRepository;
use NPS\CoreBundle\Entity\User;

/**
 * Class ItemApiService
 *
 * @package NPS\ApiBundle\Services\Entity
 */
class ItemApiService
{
    /** @var $entityManager EntityManager */
    protected $entityManager;

    /** @var SecureService */
    private $secure;

    /**
     * ItemApiService constructor.
     *
     * @param EntityManager $entityManager
     * @param SecureService $secure
     */
    public function __construct(EntityManager $entityManager, SecureService $secure)
    {
        $this->entityManager = $entityManager;
        $this->secure        = $secure;
    }

    /**
     * Sync viewed items and download unread items
     *
     * @param User  $user
     * @param array $items array of all items from API with basic information
     * @param int   $limit max quantity of items to sync
     *
     * @return array
     */
    public function syncItems(User $user, $items, $limit = 100)
    {
        $error  = false;
        $result = array();
        list($unreadItems, $readItems) = ArrayHelper::separateBooleanArray($items, 'is_unread');
        if (empty($error) && is_array($readItems) && count($readItems)) {
            $this->entityManager->getRepository('NPSCoreBundle:UserItem')->syncViewedItems($readItems);
        }

        if (!$error && $limit > 1) {
            $result = $this->getUnreadItems($user->getId(), $unreadItems, $limit);
        }

        $responseData = array(
            'error' => $error,
            'items' => $result,
        );

        return $responseData;
    }

    /**
     * Get unread items and mix them with read on server
     *
     * @param int   $userId
     * @param array $unreadItems
     * @param int   $limit
     *
     * @return array
     */
    protected function getUnreadItems($userId, array $unreadItems, $limit)
    {
        $readItems      = array();
        $itemRepo       = $this->entityManager->getRepository('NPSCoreBundle:Item');
        $userItemRepo   = $this->entityManager->getRepository('NPSCoreBundle:UserItem');
        $unreadIds      = ArrayHelper::getIdsFromArray($unreadItems, 'api_id');
        $totalUnread    = $userItemRepo->totalUnreadFeedItems($userId);
        $unreadItems    = $this->getUnreadItemsIdsRecursive($userItemRepo, $userId, $unreadIds, 0, $limit, $totalUnread);
        $unreadItemsIds = ArrayHelper::getIdsFromArray($unreadItems, 'item_id');
        $items          = array();
        if (count($unreadItemsIds)) {
            $itemsAlone = $itemRepo->getUnreadApi($unreadItemsIds);
            $items      = $this->mergeUserItemsWithItems($unreadItems, $itemsAlone);
        }

        if (count($unreadIds)) {
            $readItems = $userItemRepo->getReadItems($userId, $unreadIds);
        }
        if (count($readItems)) {
            $items = $this->addReadItems($items, $readItems);
        }

        return $items;
    }

    /**
     * Get unread items recursively
     *
     * @param UserItemRepository $userItemRepo
     * @param int                $userId    user id
     * @param array              $unreadIds still unread items ids from api
     * @param int                $begin     position from which begin limit in query
     * @param int                $limit     limit of items for query
     * @param int                $total     total unread items in data base
     *
     * @return array
     */
    private function getUnreadItemsIdsRecursive(UserItemRepository $userItemRepo, $userId, array $unreadIds, $begin, $limit, $total)
    {
        $unreadItems = $userItemRepo->getUnreadFeedItems($userId, $begin, $limit);
        if (!count($unreadIds)) {
            return $unreadItems;
        }

        $unreadItems = ArrayHelper::filterUnreadItemsIds($unreadItems, $unreadIds);
        $unreadCount = count($unreadItems);
        $begin       = $begin + $limit;

        if ($unreadCount >= $limit || ($begin + 1) >= $total || $limit < 5) { //added 5 just in case to don't do a lot of loops for few items
            return $unreadItems;
        }

        $limit -= $unreadCount;
        if (($begin + $limit) > $total) {
            $limit = $total - $begin;
        }
        $moreUnreadItems = $this->getUnreadItemsIdsRecursive($userItemRepo, $userId, $unreadIds, $begin, $limit, $total);
        $unreadItems     = array_merge($unreadItems, $moreUnreadItems);

        return $unreadItems;
    }

    /**
     * Merge user items data with items
     *
     * @param array $unreadItems
     * @param array $items
     *
     * @return array
     */
    private function mergeUserItemsWithItems($unreadItems, $items)
    {
        //echo 'tut: '.count($unreadItems).' - '.count($items); exit;

        $newItems = array();
        foreach ($items as $item) {
            foreach ($unreadItems as $key => $unreadItem) {
                if ((array_key_exists('item_id', $item) && $item['item_id'] == $unreadItem['item_id'])
                    || (array_key_exists('api_id', $item) && $item['api_id'] == $unreadItem['api_id'])
                ) {

                    $item['api_id']    = (int) $unreadItem['api_id'];
                    $item['is_stared'] = ($unreadItem['is_stared']) ? true : false;
                    $item['is_unread'] = ($unreadItem['is_unread']) ? true : false;
                    unset($item['item_id']);
                    $newItems[] = $item;
                    unset($unreadItems[$key]);
                }
            }
        }

        return $newItems;
    }

    /**
     * Add read items which came as unread from api
     *
     * @param array $items
     * @param array $readItems
     *
     * @return array
     */
    private function addReadItems($items, $readItems)
    {
        foreach ($readItems as $readItem) {
            $item    = [
                'api_id'    => $readItem['api_id'],
                'ui_id'     => 0,
                'feed_id'   => 0,
                'is_stared' => false,
                'is_unread' => false,
                'date_add'  => 0,
                'language'  => "",
                'link'      => "",
                'title'     => "",
                'content'   => ""
            ];
            $items[] = $item;
        }

        return $items;
    }
}
