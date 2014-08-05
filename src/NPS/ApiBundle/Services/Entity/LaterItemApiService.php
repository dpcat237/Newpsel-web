<?php
namespace NPS\ApiBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\ArrayHelper;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Repository\LaterItemRepository;

/**
 * LaterItemApiService
 */
class LaterItemApiService
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
     * Sync viewed later items and download unread later items
     *
     * @param string $appKey app/device key
     * @param array  $items  array of all items from API with basic information
     * @param array  $labels array of labels from which sync items
     * @param int    $limit  max quantity of items to sync
     *
     * @return array
     */
    public function syncLaterItems($appKey, $items, $labels, $limit)
    {
        $error = false;
        $result = array();

        $user = $this->secure->getUserByDevice($appKey);
        if (!$user instanceof User) {
            $error = NotificationHelper::ERROR_NO_LOGGED;
        }

        list($unreadItems, $readItems) = ArrayHelper::separateBooleanArray($items, 'is_unread');
        if (empty($error) && is_array($readItems) && count($readItems)) {
            $this->doctrine->getRepository('NPSCoreBundle:LaterItem')->syncViewedLaterItems($readItems);
        }

        if (!$error && $limit > 1) {
            $result = $this->getUnreadItems($labels, $unreadItems, $limit);
        }

        $responseData = array(
            'error' => $error,
            'later_items' => $result,
        );

        return $responseData;
    }

    /**
     * Get unread items and mix them with read on server
     *
     * @param array $labels
     * @param array $unreadItems
     * @param int   $limit
     *+
     * @return array
     */
    protected function getUnreadItems(array $labels, array $unreadItems, $limit)
    {
        $readItems = array();
        $laterItemRepo = $this->doctrine->getRepository('NPSCoreBundle:LaterItem');
        $unreadIds = ArrayHelper::getIdsFromArray($unreadItems, 'api_id');
        $labelsIds = ArrayHelper::getIdsFromArray($labels, 'api_id');
        $totalUnread = $laterItemRepo->totalUnreadLabelsItems($labelsIds);
        $items = $this->getUnreadItemsIdsRecursive($laterItemRepo, $labelsIds, $unreadIds, 0, $limit+5, $totalUnread); //"+5" extra to don't do many loops for few items

        if (count($unreadIds)) {
            $readItems = $laterItemRepo->getReadItems($unreadIds);
        }
        if (count($readItems)) {
            $items = $this->addReadItems($items, $readItems);
        }

        return $items;
    }

    /**
     * Get unread later items recursively
     *
     * @param LaterItemRepository $laterItemRepo
     * @param array               $labelsIds     array of labels ids from which sync items
     * @param array               $unreadIds     still unread items ids from api
     * @param int                 $begin         position from which begin limit in query
     * @param int                 $limit         limit of items for query
     * @param int                 $total         total unread items in data base
     *
     * @return array
     */
    private function getUnreadItemsIdsRecursive(LaterItemRepository $laterItemRepo, array $labelsIds, array $unreadIds, $begin, $limit, $total)
    {
        $unreadItems = $laterItemRepo->getUnreadForApiByLabels($labelsIds, $begin, $limit);
        if (!count($unreadIds)) {
            return $unreadItems;
        }

        $unreadItems = ArrayHelper::filterUnreadItemsIds($unreadItems, $unreadIds);
        $unreadCount = count($unreadItems);
        $begin = $begin + $limit;

        if ($unreadCount >= $limit || ($begin + 1) >= $total || $limit < 5) { //added 5 just in case to don't do a lot of loops for few items
            return $unreadItems;
        }

        $limit -= $unreadCount;
        if (($begin + $limit) > $total) {
            $limit = $total - $begin;
        }
        $moreUnreadItems = $this->getUnreadItemsIdsRecursive($laterItemRepo, $labelsIds, $unreadIds, $begin, $limit, $total);
        $unreadItems = array_merge($unreadItems, $moreUnreadItems);

        return $unreadItems;
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
                'content' => ""
            );
            $laterItems[] = $item;
        }

        return $laterItems;
    }
}
