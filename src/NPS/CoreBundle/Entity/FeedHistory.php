<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NPS\CoreBundle\Entity\AbstractEntity;
use NPS\CoreBundle\Entity\Traits\EnabledTrait;

/**
 * FeedHistory
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\FeedHistoryRepository")
 * @ORM\Table(name="feed_history")
 * @ORM\HasLifecycleCallbacks
 */
class FeedHistory extends AbstractEntity
{
    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="Feed", inversedBy="history", cascade={"persist"})
     * @ORM\JoinColumn(name="feed_id", referencedColumnName="id", nullable=true)
     */
    protected $feed;

    /**
     * @var integer
     * @ORM\Column(name="count_waiting", type="integer", nullable=true)
     */
    protected $countWaiting = 1;

    /**
     * @var boolean
     *
     * @ORM\Column(name="finished", type="boolean", nullable=true)
     */
    protected $finished = false;


    /**
     * Set countWaiting
     * @param string $countWaiting
     *
     * @return FeedHistory
     */
    public function setCountWaiting($countWaiting)
    {
        $this->countWaiting = $countWaiting;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getCountWaiting()
    {
        return $this->countWaiting;
    }

    /**
     * Plus one to count waiting
     *
     * @return string
     */
    public function countWaitingPlus()
    {
        $this->countWaiting++;
    }

    /**
     * Set finished
     * @param boolean $finished
     *
     * @return FeedHistory
     */
    public function setFinished($finished)
    {
        $this->finished = $finished;

        return $this;
    }

    /**
     * Is finished
     *
     * @return string 
     */
    public function isFinished()
    {
        return $this->finished;
    }

    /**
     * Get the feed
     *
     * @return FeedH
     */
    public function getFeed()
    {
        return $this->feed;
    }

    /**
     * Set the feed
     * @param Feed $feed
     */
    public function setFeed(Feed $feed)
    {
        $this->feed = $feed;
    }

    /**
     * Get the feed id
     *
     * @return integer id
     */
    public function getFeedId()
    {
        if (is_object($this->getFeed())) {
            $feedId = $this->getFeed()->getId();
        } else {
            $feedId = 0;
        }

        return $feedId;
    }
}
