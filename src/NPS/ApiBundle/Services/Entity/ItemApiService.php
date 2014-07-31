<?php
namespace NPS\ApiBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Helper\ArrayHelper;
use NPS\CoreBundle\Services\Entity\ItemService;
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
     * @var ItemService
     */
    private $itemService;

    /**
     * @var SecureService
     */
    private $secure;


    /**
     * @param Registry      $doctrine    Doctrine Registry
     * @param ItemService   $itemService ItemService
     * @param SecureService $secure      SecureService
     */
    public function __construct(Registry $doctrine, ItemService $itemService, SecureService $secure)
    {
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->itemService = $itemService;
        $this->secure = $secure;
    }

    /**
     * Sync shared item from api
     * @param $appKey
     * @param $sharedItems
     *
     * @return array
     */
    public function syncShared($appKey, $sharedItems)
    {
        $error = false;
        $result = false;

        $user = $this->secure->getUserByDevice($appKey);
        if (!$user instanceof User) {
            $error = NotificationHelper::ERROR_NO_LOGGED;
            $result = NotificationHelper::ERROR_NO_LOGGED;
        }

        if (empty($error) && is_array($sharedItems) && count($sharedItems)){
            $this->itemService->addSharedItems($user, $sharedItems);
            $result = NotificationHelper::OK;
        }
        $responseData = array(
            'error' => $error,
            'result' => $result,
        );

        return $responseData;
    }

    /**
     * Sync viewed items and download unread items if is required
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

        if (!$error) {
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
        $readItems = array();
        $itemRepo = $this->doctrine->getRepository('NPSCoreBundle:Item');
        $unreadIds = ArrayHelper::getIdsFromArray($unreadItems);
        $items = $itemRepo->getUnreadApi($userId, $unreadIds, $limit);

        if (count($unreadIds)) {
            $readItems = $itemRepo->getReadItems($unreadIds);
        }
        if (count($readItems)) {
            $items = $this->addReadItems($items, $readItems);
        }

        return $items;
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
