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
    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="Feed", inversedBy="filterFeeds")
     * @ORM\JoinColumn(name="feed_id", referencedColumnName="id", nullable=false)
     */
    protected $feed;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="Filter", inversedBy="filterFeeds")
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

    /**
     * Set title
     * @param string $title
     *
     * @return FilterFeed
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get the user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the user
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the user id
     *
     * @return integer id
     */
    public function getUserId()
    {
        if (is_object($this->getUser())) {
            $userId = $this->getUser()->getId();
        } else {
            $userId = 0;
        }

        return $userId;
    }
}
