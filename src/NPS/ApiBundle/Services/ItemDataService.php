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
     * @param Doctrine             $doctrine      Doctrine
     * @param SecureService        $secure        SecureService
     */
    public function __construct($doctrine, SecureService $secure)
    {
        $this->doctrine = $doctrine;
        $this->secure = $secure;
    }

    /**
     * Add feed for api
     * @param string $appKey
     * @param string $feedUrl
     *
     * @return array
     */
    public function addFeed($appKey, $feedUrl)
    {
        $error = false;
        $unreadItems = array();

        $user = $this->secure->getUserByDevice($appKey);
        if (!$user instanceof User) {
            $error = NotificationHelper::ERROR_NO_LOGGED;
        }

        $checkCreate = $this->downloadDeeds->createFeed($feedUrl, $user);
        if (!empty($checkCreate['error'])) {
            $error = NotificationHelper::ERROR_WRONG_FEED;
        }

        if (empty($error)){
            $itemRepo = $this->doctrine->getRepository('NPSCoreBundle:Item');
            $feed = $checkCreate['feed'];
            $unreadItems = $itemRepo->getUnreadItemsApi($user->getId(), $feed->getId());
        }
        $responseData = array(
            'error' => $error,
            'unreadItems' => $unreadItems,
        );

        return $responseData;
    }
}
