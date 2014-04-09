<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NPS\CoreBundle\Entity\Traits\DeletedTrait;

/**
 * FilterFeed
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\FilterFeedRepository")
 * @ORM\Table(name="filter_feed")
 * @ORM\HasLifecycleCallbacks
 */
class FilterFeed extends AbstractEntity
{
    use DeletedTrait;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="Feed", inversedBy="filterFeeds", cascade={"persist"})
     * @ORM\JoinColumn(name="feed_id", referencedColumnName="id", nullable=false)
     */
    protected $feed;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="Filter", inversedBy="filterFeeds", cascade={"persist"})
     * @ORM\JoinColumn(name="filter_id", referencedColumnName="id", nullable=false)
     */
    protected $filter;


    /**
     * Get the feed
     *
     * @return Feed
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
     * Get filter
     *
     * @return Filter
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Set filter
     * @param Filter $filter
     *
     * @return FilterFeed
     */
    public function setFilter(Filter $filter)
    {
        $this->filter = $filter;

        return $this;
    }
}
