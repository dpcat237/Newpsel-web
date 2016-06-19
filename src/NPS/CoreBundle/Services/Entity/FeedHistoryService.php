<?php

namespace NPS\CoreBundle\Services\Entity;

use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Entity\FeedHistory;
use NPS\CoreBundle\Repository\FeedHistoryRepository;

/**
 * FeedHistoryService
 */
class FeedHistoryService extends AbstractEntityService
{
    CONST MIN_INTERVAL = 900;   //60*15    = 900 seconds   = 15 minutes
    CONST MAX_INTERVAL = 86400; //60*60*24 = 86400 seconds = 24 hours

    /** @var FeedHistoryRepository */
    protected $feedHistoryRepository;

    /**
     * @inheritdoc
     */
    protected function setRepository()
    {
        $this->feedHistoryRepository = $this->entityManager->getRepository(FeedHistory::class);
    }

    /**
     * Add new feed's history and put counter to one
     *
     * @param Feed $feed
     */
    private function addNewHistory(Feed $feed)
    {
        $feedHistory = new FeedHistory();
        $feedHistory->setFeed($feed);
        $this->entityManager->persist($feedHistory);
        $this->entityManager->flush();
    }

    /**
     * Check if it's necessary decrease sync interval
     *
     * @param Feed  $feed        feed object
     * @param array $historyData feed sync history
     */
    protected function checkDecreaseInterval(Feed $feed, $historyData)
    {
        if ($historyData['countAvg'] < 2 && $historyData['countMin'] == 1 && $feed->getSyncInterval() != self::MIN_INTERVAL) {
            $newSyncInterval = (($feed->getSyncInterval() - self::MIN_INTERVAL) < self::MIN_INTERVAL) ? self::MIN_INTERVAL : $feed->getSyncInterval() - self::MIN_INTERVAL;
            $this->changeSyncInterval($feed, $newSyncInterval);
        }
    }

    /**
     * Check if is necessary increase sync interval
     *
     * @param Feed  $feed        feed object
     * @param array $historyData feed sync history
     */
    protected function checkIncreaseInterval(Feed $feed, $historyData)
    {
        //increase one hour
        if ($historyData['countMin'] > 50) {
            $newSyncInterval = (($feed->getSyncInterval() + (60 * 60)) > self::MAX_INTERVAL) ? self::MAX_INTERVAL : $feed->getSyncInterval() + (60 * 60);
            $this->changeSyncInterval($feed, $newSyncInterval);

            return;
        }

        //increase half hour
        if ($historyData['countMin'] > 20) {
            $newSyncInterval = (($feed->getSyncInterval() + (60 * 30)) > self::MAX_INTERVAL) ? self::MAX_INTERVAL : $feed->getSyncInterval() + (60 * 30);
            $this->changeSyncInterval($feed, $newSyncInterval);

            return;
        }
        //increase 15 minutes
        if ($historyData['countMin'] > 1 && $historyData['countAvg'] > 2) {
            $newSyncInterval = (($feed->getSyncInterval() + (60 * 15)) > self::MAX_INTERVAL) ? self::MAX_INTERVAL : $feed->getSyncInterval() + (60 * 15);
            $this->changeSyncInterval($feed, $newSyncInterval);
        }
    }

    /**
     * Change feed's sync interval
     *
     * @param Feed $feed            feed object
     * @param int  $newSyncInterval new sync interval in seconds
     */
    protected function changeSyncInterval(Feed $feed, $newSyncInterval)
    {
        $feed->setSyncInterval($newSyncInterval);
        $this->entityManager->persist($feed);
        $this->entityManager->flush();
    }

    /**
     * If feed history exist set finished to true
     *
     * @param Feed $feed Feed
     */
    public function dataChanged(Feed $feed)
    {
        $feedHistory = $this->feedHistoryRepository->getLastHistory($feed->getId());
        if ($feedHistory instanceof FeedHistory) {
            $this->setFinished($feedHistory);
        }
    }

    /**
     * Save necessary data if feed's data is same
     *
     * @param Feed $feed
     *
     * @return null
     */
    public function dataIsSame(Feed $feed)
    {
        $feedHistory = $this->feedHistoryRepository->getLastHistory($feed->getId());
        if (!$feedHistory instanceof FeedHistory) {
            $this->addNewHistory($feed);

            return;
        }

        $historyLimit = time() - (60 * 60 * 24);
        if ($feedHistory->isFinished() || $feedHistory->getDateAdd() < $historyLimit) {
            $this->addNewHistory($feed);
        } else {
            $this->plusCounter($feedHistory);
        }
    }

    /**
     * Plus one in counter
     *
     * @param FeedHistory $feedHistory
     */
    private function plusCounter(FeedHistory $feedHistory)
    {
        $feedHistory->countWaitingPlus();
        $this->entityManager->persist($feedHistory);
        $this->entityManager->flush();
    }

    /**
     * Set finished to true
     *
     * @param FeedHistory $feedHistory
     */
    private function setFinished(FeedHistory $feedHistory)
    {
        $feedHistory->setFinished(true);
        $this->entityManager->persist($feedHistory);
        $this->entityManager->flush();
    }

    /**
     * Update feed's sync history
     *
     * @param Feed $feed          feed object
     * @param int  $countNewItems count of new items added from source to data base
     */
    public function updateFeedSyncHistory(Feed $feed, $countNewItems)
    {
        if ($countNewItems > 0) {
            $this->dataChanged($feed);
        } else {
            $this->dataIsSame($feed);
        }
    }

    /**
     * If is necessary update feed sync interval
     *
     * @param Feed $feed
     */
    public function updateSyncInterval(Feed $feed)
    {
        $feedDayHistory = $this->feedHistoryRepository->getDayHistory($feed->getId());
        if (!is_array($feedDayHistory) || !$feedDayHistory['id']) {
            return;
        }

        if ($feedDayHistory['countAvg'] < 2 && $feedDayHistory['countMin'] == 1 && $feed->getSyncInterval() > self::MIN_INTERVAL) {
            $this->checkDecreaseInterval($feed, $feedDayHistory);
        }

        if ($feedDayHistory['countMin'] > 1 && $feed->getSyncInterval() < self::MAX_INTERVAL) {
            $this->checkIncreaseInterval($feed, $feedDayHistory);
        }
    }
}
