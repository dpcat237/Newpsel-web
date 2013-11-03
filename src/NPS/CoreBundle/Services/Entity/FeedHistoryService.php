<?php
namespace NPS\CoreBundle\Services\Entity;

use NPS\CoreBundle\Entity\Feed,
    NPS\CoreBundle\Entity\FeedHistory;
use NPS\CoreBundle\Services\Entity\AbstractEntityService;

/**
 * FeedHistoryService
 */
class FeedHistoryService extends AbstractEntityService
{
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
     * If feed history exist set finished to true
     *
     * @param Feed $feed Feed
     */
    public function dataChanged(Feed $feed)
    {
        $feedHistoryRepo = $this->doctrine->getRepository('NPSCoreBundle:FeedHistory');
        $feedHistory = $feedHistoryRepo->getLastHistory($feed->getId());

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
        $feedHistoryRepo = $this->doctrine->getRepository('NPSCoreBundle:FeedHistory');
        $feedHistory = $feedHistoryRepo->getLastHistory($feed->getId());

        if (!$feedHistory instanceof FeedHistory) {
            $this->addNewHistory($feed);

            return;
        }

        if ($feedHistory->isFinished()) {
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
}
