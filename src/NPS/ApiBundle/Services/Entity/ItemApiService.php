<?php
namespace NPS\ApiBundle\Services\Entity;

use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Services\Entity\ItemService;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * ItemApiService
 */
class ItemApiService
{
    /**
     * @var $doctrine Doctrine
     */
    private $doctrine;

    /**
     * @var $itemService ItemService
     */
    private $itemService;

    /**
     * @var $secure SecureService
     */
    private $secure;


    /**
     * @param Doctrine      $doctrine    Doctrine
     * @param ItemService   $itemService ItemService
     * @param SecureService $secure      SecureService
     */
    public function __construct($doctrine, ItemService $itemService, SecureService $secure)
    {
        $this->doctrine = $doctrine;
        $this->itemService = $itemService;
        $this->secure = $secure;
    }

    /**
     * Add page for Chrome api
     * @param $appKey
     * @param $labelId
     * @param $webTitle
     * @param $webUrl
     *
     * @return array
     */
    public function addPage($appKey, $labelId, $webTitle, $webUrl)
    {
        $response = false;
        $user = $this->secure->getUserByDevice($appKey);
        if ($user instanceof User) {
            $this->itemService->addPageToLater($user, $labelId, $webTitle, $webUrl);
            $response = true;
        }
        $responseData = array(
            'response' => $response
        );

        return $responseData;
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
     * @param $appKey
     * @param $viewedItems
     * @param $download
     *
     * @return array
     */
    public function syncUnreadItems($appKey, $viewedItems, $download)
    {
        $error = false;
        $unreadItems = array();

        $user = $this->secure->getUserByDevice($appKey);
        if (!$user instanceof User) {
            $error = NotificationHelper::ERROR_NO_LOGGED;
        }

        if (empty($error)){
            $unreadItems = $this->syncUnreadItemsProcess($user, $viewedItems, $download);
        }
        $responseData = array(
            'error' => $error,
            'unreadItems' => $unreadItems,
        );

        return $responseData;
    }

    /**
     * If aren't errors sync unread items
     * @param $user
     * @param $viewedItems
     * @param $download
     *
     * @return array
     */
    protected function syncUnreadItemsProcess(User $user, $viewedItems, $download)
    {
        $unreadItems = array();
        $itemRepo = $this->doctrine->getRepository('NPSCoreBundle:Item');
        if (is_array($viewedItems) && count($viewedItems)) {
            $itemRepo->syncViewedItems($user->getId(), $viewedItems);
        }

        if ($download) {
            $unreadItems = $itemRepo->getUnreadItemsApi($user->getId());
        }

        return $unreadItems;
    }
}
