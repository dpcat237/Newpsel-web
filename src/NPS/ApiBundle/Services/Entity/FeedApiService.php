<?php

namespace NPS\ApiBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\CoreBundle\Event\FeedModifiedEvent;
use NPS\CoreBundle\Services\Entity\FeedService;
use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Constant\EntityConstants;
use NPS\CoreBundle\NPSCoreEvents;
use NPS\CoreBundle\Services\DownloadFeedsService;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class FeedApiService
 *
 * @package NPS\ApiBundle\Services\Entity
 */
class FeedApiService
{
    /** @var DownloadFeedsService */
    protected $downloadFeeds;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var FeedService */
    protected $feedService;

    /** @var SecureService */
    protected $secure;

    /** @var Boolean */
    protected $modified = false;

    /**
     * FeedApiService constructor.
     *
     * @param FeedService              $feedService
     * @param SecureService            $secure
     * @param DownloadFeedsService     $downloadFeeds
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        FeedService $feedService,
        SecureService $secure,
        DownloadFeedsService $downloadFeeds,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->feedService     = $feedService;
        $this->downloadFeeds   = $downloadFeeds;
        $this->eventDispatcher = $eventDispatcher;
        $this->secure          = $secure;
    }

    /**
     * Add feed for api
     *
     * @param string $appKey
     * @param string $feedUrl
     *
     * @return array
     */
    public function addFeed($appKey, $feedUrl)
    {
        $user = $this->secure->getUserByDevice($appKey);
        if ($user instanceof User) {
            list($feed, $error) = $this->downloadFeeds->addFeed($feedUrl, $user);
        } else {
            return NotificationHelper::ERROR_NO_LOGGED;
        }

        if ($error) {
            return NotificationHelper::ERROR_WRONG_FEED;
        }

        $this->eventDispatcher->dispatch(NPSCoreEvents::FEED_MODIFIED, new FeedModifiedEvent($user->getId()));

        return NotificationHelper::OK;
    }

    /**
     * Change feed title
     *
     * @param User   $user
     * @param int    $feedId
     * @param string $feedTitle
     */
    public function editFeed(User $user, $feedId, $feedTitle)
    {
        $this->feedService->editUserFeed($user->getId(), $feedId, $feedTitle);
        $this->eventDispatcher->dispatch(NPSCoreEvents::FEED_MODIFIED, new FeedModifiedEvent($user->getId()));
    }

    /**
     * Soft remove user's feed
     *
     * @param User $user
     * @param int  $feedId
     */
    public function unsubscribeFeed(User $user, $feedId)
    {
        $userFeed = $this->feedService->getUserFeed($user->getId(), $feedId);
        $this->feedService->removeUserFeed($userFeed);
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

        if (!$error) {
            $dbFeeds = $this->feedService->getUserFeedsApi($user->getId());
            $feeds   = $this->processFeedsSync($dbFeeds, $apiFeeds, $user);
        }
        $responseData = [
            'error' => $error,
            'feeds' => $feeds,
        ];

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
        $feeds          = array();
        $this->modified = false;
        //first sync to device
        if (!count($apiFeeds)) {
            foreach ($dbFeeds as $dbFeed) {
                if ($dbFeed['deleted']) {
                    continue;
                }
                $dbFeed['status'] = EntityConstants::STATUS_NEW;
                $feeds[]          = $dbFeed;
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

        foreach ($dbFeeds as $dbFeed) {
            $found = false;
            foreach ($apiFeeds as $keyApi => $apiFeed) {
                if ($dbFeed['api_id'] != $apiFeed['api_id']) {
                    continue;
                }
                $apiFeed['title'] = utf8_encode($apiFeed['title']);

                //deleted notify API
                if ($dbFeed['deleted']) {
                    $apiFeed['status'] = EntityConstants::STATUS_DELETED;
                    $feeds[]           = $apiFeed;
                    $found             = true;
                    break;
                }

                //any change
                if ($dbFeed['date_up'] == $apiFeed['date_up']) {
                    unset($apiFeeds[$keyApi]);
                    $found = true;
                    break;
                }

                //compare changes
                $changedLabel = $this->processChangedFeed($dbFeed, $apiFeed);
                if (!empty($changedLabel)) {
                    $feeds[] = $changedLabel;
                }

                unset($apiFeeds[$keyApi]);
                $found = true;
                break;
            }
            if (!$found) {
                $dbFeed['status'] = EntityConstants::STATUS_NEW;
                $feeds[]          = $dbFeed;
            }
        }

        return $feeds;
    }

    /**
     * Compare last update dates and update feed in proper place
     *
     * @param array $dbFeed
     * @param array $apiFeed
     *
     * @return null
     */
    private function processChangedFeed($dbFeed, $apiFeed)
    {
        if ($dbFeed['date_up'] > $apiFeed['date_up']) {
            $dbFeed['status'] = EntityConstants::STATUS_CHANGED;
            $dbFeed['id']     = $apiFeed['id'];

            return $dbFeed;
        }
        if ($dbFeed['date_up'] < $apiFeed['date_up']) {
            $this->feedService->updateUserFeed($apiFeed['api_id'], $apiFeed['title'], $apiFeed['date_up']);
            $this->modified = true;

            return null;
        }
    }
}
