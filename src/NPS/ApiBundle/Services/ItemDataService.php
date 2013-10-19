<?php
namespace NPS\ApiBundle\Services;

use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * ItemDataService
 */
class ItemDataService
{
    /**
     * @var $doctrine Doctrine
     */
    private $doctrine;


    /**
     * @param Doctrine      $doctrine Doctrine
     * @param SecureService $secure   SecureService
     */
    public function __construct($doctrine, SecureService $secure)
    {
        $this->doctrine = $doctrine;
        $this->secure = $secure;
    }

    /**
     * Sync viewed items and download unread items if is required
     * @param $appKey
     * @param $viewedItems
     * @param $download
     *
     * @return array
     */
    public function syncUnreadItem($appKey, $viewedItems, $download)
    {
        $error = false;
        $unreadItems = array();

        $user = $this->secure->getUserByDevice($appKey);
        if (!$user instanceof User) {
            $error = NotificationHelper::ERROR_NO_LOGGED;
        }

        if (empty($error)){
            $itemRepo = $this->doctrine->getRepository('NPSCoreBundle:Item');
            if (is_array($viewedItems) && count($viewedItems)) {
                $itemRepo->syncViewedItems($user->getId(), $viewedItems);
            }

            if ($download) {
                $unreadItems = $itemRepo->getUnreadItemsApi($user->getId());
            }
        }
        $responseData = array(
            'error' => $error,
            'unreadItems' => $unreadItems,
        );

        return $responseData;
    }
}
