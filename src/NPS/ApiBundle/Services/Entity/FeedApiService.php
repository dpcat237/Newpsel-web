<?php
namespace NPS\ApiBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\CoreBundle\Event\FeedModifiedEvent;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Constant\EntityConstants;
use NPS\CoreBundle\NPSCoreEvents;
use NPS\CoreBundle\Repository\FeedRepository;
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
    private $downloadFeeds;

    /**
     * @var ContainerAwareEventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var SecureService
     */
    private $secure;

    /**
     * @var Boolean
     */
    private $modified = false;


    /**
     * @param Registry                      $doctrine        Doctrine Registry
     * @param SecureService                 $secure          SecureService
     * @param DownloadFeedsService          $downloadFeeds   DownloadFeedsService
     * @param ContainerAwareEventDispatcher $eventDispatcher ContainerAwareEventDispatcher
     */
    public function __construct(Registry $doctrine, SecureService $secure, DownloadFeedsService $downloadFeeds, ContainerAwareEventDispatcher $eventDispatcher)
    {
        $this->doctrine = $doctrine;
        $this->downloadFeeds = $downloadFeeds;
        $this->eventDispatcher = $eventDispatcher;
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
        if ($user instanceof User) {
            $checkCreate = $this->downloadFeeds->addFeed($feedUrl, $user);
        } else {
            $error = NotificationHelper::ERROR_NO_LOGGED;
        }


        if (!empty($checkCreate['error'])) {
            $error = NotificationHelper::ERROR_WRONG_FEED;
        }

        if (empty($error)){
            $itemRepo = $this->doctrine->getRepository('NPSCoreBundle:Item');
            $feed = $checkCreate['feed'];
            $unreadItems = $itemRepo->getUnreadItems($user->getId(), $feed->getId());

            //notify other devices about modification
            $this->eventDispatcher->dispatch(NPSCoreEvents::FEED_MODIFIED, new FeedModifiedEvent($user->getId()));
        }
        $responseData = array(
            'error' => $error,
            'unreadItems' => $unreadItems,
        );

        return $responseData;
    }

    /**
     * Get feed to sync with api
     *
     * @param string $appKey
     * @param array  $apiFeeds
     *
     * @return array
     */
    public function syncFeeds($appKey, array $apiFeeds)
    {
        $error = false;
        $feeds = array();

        $user = $this->secure->getUserByDevice($appKey);
        if (!$user instanceof User) {
            $error = NotificationHelper::ERROR_NO_LOGGED;
        }

        if (!$error){
            $dbFeeds = $this->doctrine->getRepository('NPSCoreBundle:Feed')->getUserFeedsApi($user->getId());
            $feeds = $this->processFeedsSync($dbFeeds, $apiFeeds, $user);
        }
        $responseData = array(
            'error' => $error,
            'feeds' => $feeds,
        );

        return $responseData;
    }

    /**
     * Process feeds updating if necessary data in data base and get new data to return to API
     *
     * @param array $dbFeeds
     * @param array $apiFeeds
     * @param User  $user
     *
     * @return array
     */
    private function processFeedsSync(array $dbFeeds, array $apiFeeds, $user)
    {
        $feeds = array();
        $this->modified = false;
        //first sync to device
        if (!count($apiFeeds)) {
            foreach ($dbFeeds as $dbFeed) {
                if ($dbFeed['deleted']) {
                    continue;
                }
                $dbFeed['status'] = EntityConstants::STATUS_NEW;
                $feeds[] = $dbFeed;
            }

            return $feeds;
        }

        //compare feeds from API and server data base
        $changedFeeds = $this->compareSyncApiDb($dbFeeds, $apiFeeds);
        if (count($changedFeeds)) {
            $feeds = array_merge($feeds, $changedFeeds);
        }

        //if it's necessary notify about changes other devices
        if ($this->modified) {
            //notify other devices about modification
            $this->eventDispatcher->dispatch(NPSCoreEvents::FEED_MODIFIED, new FeedModifiedEvent($user->getId()));
        }

        return $feeds;
    }

    /**
     * Compare feeds from API and data base to find difference
     *
     * @param array $dbFeeds
     * @param array $apiFeeds
     *
     * @return array
     */
    private function compareSyncApiDb(array $dbFeeds, array $apiFeeds)
    {
        $feeds = array();
        $feedRepo = $this->doctrine->getRepository('NPSCoreBundle:Feed');

        foreach ($dbFeeds as $dbFeed) {
            $found = false;
            foreach ($apiFeeds as $keyApi => $apiFeed) {
                if ($dbFeed['api_id'] != $apiFeed['api_id']) {
                    continue;
                }

                //deleted notify API
                if ($dbFeed['deleted']) {
                    $apiFeed['status'] = EntityConstants::STATUS_DELETED;
                    $feeds[] = $apiFeed;
                    $found = true;
                    break;
                }

                //any change
                if ($dbFeed['date_up'] == $apiFeed['date_up']) {
                    unset($apiFeeds[$keyApi]);
                    $found = true;
                    break;
                }

                //compare changes
                $changedLabel = $this->processChangedFeed($dbFeed, $apiFeed, $feedRepo);
                if (!empty($changedLabel)) {
                    $feeds[] = $changedLabel;
                }

                unset($apiFeeds[$keyApi]);
                $found = true;
                break;
            }
            if (!$found) {
                $dbFeed['status'] = EntityConstants::STATUS_NEW;
                $feeds[] = $dbFeed;
            }
        }

        return $feeds;
    }

    /**
     * Compare last update dates and update feed in proper place
     *
     * @param array          $dbFeed
     * @param array          $apiFeed
     * @param FeedRepository $feedRepo
     *
     * @return null
     */
    private function processChangedFeed($dbFeed, $apiFeed, FeedRepository $feedRepo)
    {
        if ($dbFeed['date_up'] > $apiFeed['date_up']) {
            $dbFeed['status'] = EntityConstants::STATUS_CHANGED;
            $dbFeed['id'] = $apiFeed['id'];

            return $dbFeed;
        }
        if ($dbFeed['date_up'] < $apiFeed['date_up']) {
            $feedRepo->updateFeed($apiFeed['api_id'], $apiFeed['title'], $apiFeed['date_up']);
            $this->modified = true;

            return null;
        }
    }
}
