<?php
namespace NPS\ApiBundle\Services;

use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Services\DownloadFeedsService;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * FeedDataService
 */
class FeedDataService
{
    /**
     * @var $doctrine Doctrine
     */
    private $doctrine;

    /**
     * @var $downloadDeeds DownloadFeedsService
     */
    private $downloadDeeds;

    /**
     * @var $secure SecureService
     */
    private $secure;


    /**
     * @param Doctrine             $doctrine      Doctrine
     * @param SecureService        $secure        SecureService
     * @param DownloadFeedsService $downloadDeeds DownloadFeedsService
     */
    public function __construct($doctrine, SecureService $secure, DownloadFeedsService $downloadDeeds)
    {
        $this->doctrine = $doctrine;
        $this->downloadDeeds = $downloadDeeds;
        $this->secure = $secure;
    }

    /**
     * Get user labels from app key
     * @param $appKey
     *
     * @return array
     */
    public function getUserLabels($appKey)
    {
        $response = false;
        $labels = array();
        $user = $this->secure->getUserByDevice($appKey);
        if ($user instanceof User) {
            $labelRepo = $this->doctrine->getRepository('NPSCoreBundle:Later');
            $orderBy = array('name' => 'ASC');
            $labelsData = $labelRepo->findByUser($user, $orderBy);

            //prepare labels for api
            foreach ($labelsData as $lab) {
                $label['id'] = $lab->getId();
                $label['name'] = $lab->getName();
                $labels[] = $label;
            }
            $response = true;
        }
        $responseData = array(
            'response' => $response,
            'labels' => $labels
        );

        return $responseData;
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
