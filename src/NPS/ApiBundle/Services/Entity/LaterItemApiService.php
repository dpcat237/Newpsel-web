<?php
namespace NPS\ApiBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\ArrayHelper;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Repository\LaterItemRepository;
use NPS\CoreBundle\Services\Entity\LaterItemService;
use NPS\CoreBundle\Services\QueueLauncherService;

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
     * @var LaterItemService
     */
    private $laterItem;

    /**
     * @var SecureService
     */
    private $secure;

    /**
     * @var QueueLauncherService
     */
    private $queueLauncher;


    /**
     * @param Registry             $doctrine      Doctrine Registry
     * @param SecureService        $secure        SecureService
     * @param QueueLauncherService $queueLauncher QueueLauncherService
     * @param LaterItemService     $laterItem     LaterItemService
     */
    public function __construct(Registry $doctrine, SecureService $secure, QueueLauncherService $queueLauncher, LaterItemService $laterItem)
    {
        $this->doctrine      = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->laterItem     = $laterItem;
        $this->secure        = $secure;
        $this->queueLauncher = $queueLauncher;
    }

    /**
     * Add page for Chrome api
     *
     * @param string $appKey
     * @param int    $labelId
     * @param string $webTitle
     * @param string $webUrl
     *
     * @return array
     */
    public function addPage($appKey, $labelId, $webTitle, $webUrl)
    {
        $response = false;
        $user     = $this->secure->getUserByDevice($appKey);
        if ($user instanceof User) {
            $this->laterItem->addPageToLater($user, $labelId, $webTitle, $webUrl, true);
            $response = true;
        }
        $this->queueLauncher->executeCrawling($user->getId());

        $responseData = array(
            'response' => $response
        );

        return $responseData;
    }

    /**
     * Sync viewed later items and download unread later items
     *
     * @param User  $user
     * @param array $items  array of all items from API with basic information
     * @param array $labels array of labels from which sync items
     * @param int   $limit  max quantity of items to sync
     *
     * @return array
     */
    public function syncLaterItems(User $user, $items, $labels, $limit)
    {
        $error  = false;
        $result = array();
        list($unreadItems, $readItems) = ArrayHelper::separateBooleanArray($items, 'is_unread');
        if (empty($error) && is_array($readItems) && count($readItems)) {
            $this->doctrine->getRepository('NPSCoreBundle:LaterItem')->syncViewedLaterItems($readItems);
        }

        if (!$error && $limit > 1) {
            $result = $this->getUnreadItems($labels, $unreadItems, $limit);
        }

        $responseData = array(
            'error'     => $error,
            'tag_items' => $result,
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
     *
     * @return array
     */
    protected function getUnreadItems(array $labels, array $unreadItems, $limit)
    {
        $readItems     = array();
        $laterItemRepo = $this->doctrine->getRepository('NPSCoreBundle:LaterItem');
        $unreadIds     = ArrayHelper::getIdsFromArray($unreadItems, 'api_id');
        $labelsIds     = ArrayHelper::getIdsFromArray($labels, 'api_id');
        $totalUnread   = $laterItemRepo->totalUnreadLabelsItems($labelsIds);
        $items         = $this->getUnreadItemsIdsRecursive($laterItemRepo, $labelsIds, $unreadIds, 0, $limit + 5, $totalUnread); //"+5" extra to don't do many loops for few items

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
     * @param array               $labelsIds array of labels ids from which sync items
     * @param array               $unreadIds still unread items ids from api
     * @param int                 $begin     position from which begin limit in query
     * @param int                 $limit     limit of items for query
     * @param int                 $total     total unread items in data base
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
        $begin       = $begin + $limit;

        if ($unreadCount >= $limit || ($begin + 1) >= $total || $limit < 5) { //added 5 just in case to don't do a lot of loops for few items
            return $unreadItems;
        }

        $limit -= $unreadCount;
        if (($begin + $limit) > $total) {
            $limit = $total - $begin;
        }
        $moreUnreadItems = $this->getUnreadItemsIdsRecursive($laterItemRepo, $labelsIds, $unreadIds, $begin, $limit, $total);
        $unreadItems     = array_merge($unreadItems, $moreUnreadItems);

        return $unreadItems;
    }

    /**
     * Add read items which came as unread from api
     *
     * @param array $tagItems
     * @param array $readItems
     *
     * @return array
     */
    private function addReadItems($tagItems, $readItems)
    {
        foreach ($readItems as $readItem) {
            $item       = array(
                'api_id'    => $readItem['api_id'],
                'item_id'   => 0,
                'feed_id'   => 0,
                'tag_id'    => 0,
                'is_unread' => false,
                'date_add'  => 0,
                'language'  => "",
                'link'      => "",
                'title'     => "",
                'content'   => ""
            );
            $tagItems[] = $item;
        }

        return $tagItems;
    }

    /**
     * Sync tag item from API to database to be read later
     *
     * @param User  $user
     * @param array $tagItems selected items to be read later
     *
     * @return array
     */
    public function syncLaterItemsApi(User $user, $tagItems)
    {
        $error  = false;
        $result = false;
        if (empty($error) && is_array($tagItems) && count($tagItems)) {
            $this->laterItem->syncLaterItems($user->getId(), $tagItems);
            //get complete content for partial articles
            $this->queueLauncher->executeCrawling($user->getId());

            $result = NotificationHelper::OK;
        }
        $responseData = array(
            'error'  => $error,
            'result' => $result,
        );

        return $responseData;
    }

    /**
     * Sync shared item from api
     *
     * @param User  $user
     * @param array $sharedItems
     *
     * @return array
     */
    public function syncShared(User $user, $sharedItems)
    {
        $error  = false;
        $result = false;
        if (empty($error) && is_array($sharedItems) && count($sharedItems)) {
            $this->addSharedItems($user, $sharedItems);
            $result = NotificationHelper::OK;
        }
        $responseData = array(
            'error'  => $error,
            'result' => $result,
        );

        return $responseData;
    }

    /**
     * Add shared pages from api
     *
     * @param User  $user
     * @param array $sharedItems
     */
    public function addSharedItems(User $user, $sharedItems)
    {
        foreach ($sharedItems as $sharedItem) {
            $this->laterItem->addPageToLater($user, $sharedItem['tag_api_id'], $sharedItem['title'], $sharedItem['text'], true);
        }
    }

    /**
     * Sync later item to API and update the reviewed items
     *
     * @param User  $user
     * @param array $dictateItems items for dictation
     * @param int   $limit        limit of dictations to sync
     *
     * @return array
     */
    public function syncDictateItems(User $user, $dictateItems, $limit)
    {
        $error  = false;
        $result = array();
        list($unreadItems, $readItems) = ArrayHelper::separateBooleanArray($dictateItems, 'is_unread');
        if (empty($error) && is_array($readItems) && count($readItems)) {
            $this->doctrine->getRepository('NPSCoreBundle:LaterItem')->syncViewedLaterItems($readItems);
        }
        if (empty($error)) {
            $result = $this->laterItem->getUnreadItemsApi($user->getPreference()->getDictationTagId(), $unreadItems, $limit);
        }
        $responseData = array(
            'error'  => $error,
            'result' => $result,
        );

        return $responseData;
    }
}
