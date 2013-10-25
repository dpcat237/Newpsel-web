<?php
namespace NPS\ApiBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Services\DownloadFeedsService;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * FeedApiService
 */
class FeedApiService
{
    /**
     * @var Doctrine
     */
    private $doctrine;

    /**
     * @var DownloadFeedsService
     */
    private $downloadDeeds;

    /**
     * @var SecureService
     */
    private $secure;


    /**
     * @param Registry             $doctrine      Doctrine Registry
     * @param SecureService        $secure        SecureService
     * @param DownloadFeedsService $downloadDeeds DownloadFeedsService
     */
    public function __construct(Registry $doctrine, SecureService $secure, DownloadFeedsService $downloadDeeds)
    {
        $this->doctrine = $doctrine;
        $this->downloadDeeds = $downloadDeeds;
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

        $checkCreate = $this->downloadFeeds->createFeed($feedUrl, $user);
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

    /**
     * Get feed to sync with api
     * @param $appKey
     * @param $lastUpdate
     *
     * @return array
     */
    public function syncFeeds($appKey, $lastUpdate)
    {
        $error = false;
        $feedCollection = array();

        $user = $this->secure->getUserByDevice($appKey);
        if ($user instanceof User) {
            $error = NotificationHelper::ERROR_NO_LOGGED;
        }

        if (!$error){
            $feedRepo = $this->doctrine->getRepository('NPSCoreBundle:Feed');
            $feedCollection = $feedRepo->getUserFeedsApi($user->getId(), $lastUpdate);
        }
        $responseData = array(
            'error' => $error,
            'feedCollection' => $feedCollection,
        );

        return $responseData;
    }
}
