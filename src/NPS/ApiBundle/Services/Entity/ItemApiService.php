<?php
namespace NPS\ApiBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Helper\ArrayHelper;
use NPS\CoreBundle\Repository\UserItemRepository;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * ItemApiService
 */
class ItemApiService
{
    /**
     * @var Doctrine Registry
     */
    private $doctrine;

    /**
     * @var $entityManager Entity Manager
     */
    protected $entityManager;

    /**
     * @var SecureService
     */
    private $secure;


    /**
     * @param Registry      $doctrine    Doctrine Registry
     * @param SecureService $secure      SecureService
     */
    public function __construct(Registry $doctrine, SecureService $secure)
    {
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->secure = $secure;
    }

    /**
     * Sync viewed items and download unread items
     *
     * @param string $appKey app/device key
     * @param array  $items  array of all items from API with basic information
     * @param int    $limit  max quantity of items to sync
     *
     * @return array
     */
    public function syncItems($appKey, $items, $limit)
    {
        $error = false;
        $result = array();

        $user = $this->secure->getUserByDevice($appKey);
        if (!$user instanceof User) {
            $error = NotificationHelper::ERROR_NO_LOGGED;
        }

        list($unreadItems, $readItems) = ArrayHelper::separateBooleanArray($items, 'is_unread');
        if (empty($error) && is_array($readItems) && count($readItems)) {
            $this->doctrine->getRepository('NPSCoreBundle:UserItem')->syncViewedItems($readItems);
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
     *+
     * @return array
     */
    protected function getUnreadItems($userId, array $unreadItems, $limit)
    {
        $readItems = array();
        $itemRepo = $this->doctrine->getRepository('NPSCoreBundle:Item');
        $userItemRepo = $this->doctrine->getRepository('NPSCoreBundle:UserItem');
        $unreadIds = ArrayHelper::getIdsFromArray($unreadItems);
        $totalUnread = $userItemRepo->totalUnreadFeedItems($userId);
        $unreadItems = $this->getUnreadItemsIdsRecursive($userItemRepo, $userId, $unreadIds, 0, $limit+5, $totalUnread); //"+5" extra to don't do many loops for few items
        $unreadItemsIds = ArrayHelper::getIdsFromArray($unreadItems, 'item_id');
        $items = array();
        if (count($unreadItemsIds)) {
            $itemsAlone = $itemRepo->getUnreadApi($unreadItemsIds);
            $items = $this->mergeUserItemsWithItems($unreadItems, $itemsAlone);
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
     * @param int                $userId       user id
     * @param array              $unreadIds    still unread items ids from api
     * @param int                $begin        position from which begin limit in query
     * @param int                $limit        limit of items for query
     * @param int                $total        total unread items in data base
     *
     * @return array
     */
    private function getUnreadItemsIdsRecursive(UserItemRepository $userItemRepo, $userId, array $unreadIds, $begin, $limit, $total)
    {
        $unreadItems = $userItemRepo->getUnreadFeedItems($userId, $begin, $limit);
        if (!count($unreadIds)) {
            return $unreadItems;
        }

        $unreadItems = ArrayHelper::filterUnreadItemsIds($unreadItems, $unreadIds, 'item_id');
        $unreadCount = count($unreadItems);
        $begin = $begin + $limit;

        if ($unreadCount >= $limit || ($begin + 1) >= $total || $limit < 5) { //added 5 just in case to don't do a lot of loops for few items
            return $unreadItems;
        }

        $limit -= $unreadCount;
        if (($begin + $limit) > $total) {
            $limit = $total - $begin;
        }
        $moreUnreadItems = $this->getUnreadItemsIdsRecursive($userItemRepo, $userId, $unreadIds, $begin, $limit, $total);
        $unreadItems = array_merge($unreadItems, $moreUnreadItems);

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
        $newItems = array();
        foreach ($items as $item) {
            foreach ($unreadItems as $key => $unreadItem) {
                if ($item['api_id'] == $unreadItem['item_id']) {
                    $item['ui_id'] = (int) $unreadItem['ui_id'];
                    $item['is_stared'] = ($unreadItem['is_stared'])? true : false;
                    $item['is_unread'] = ($unreadItem['is_unread'])? true : false;
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
            $item = array(
                'api_id' => $readItem['api_id'],
                'ui_id' => 0,
                'feed_id' => 0,
                'is_stared' => false,
                'is_unread' => false,
                'date_add' => 0,
                'language' => "",
                'link' => "",
                'title' => "",
                'content' => ""
            );
            $items[] = $item;
        }

        return $items;
    }
}
