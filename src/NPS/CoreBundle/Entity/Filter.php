<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use NPS\CoreBundle\Entity\Traits\EnabledTrait;

/**
 * Filter
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\FilterRepository")
 * @ORM\Table(name="filter")
 * @ORM\HasLifecycleCallbacks
 */
class Filter extends AbstractEntity
{
    use EnabledTrait;

    const FILTER_FEED_TO_TAG = 'feed_to_tag';

    /**
     * Filters with translation
     *
     * @var array
     */
    public static $filterTypes = [
        self::FILTER_FEED_TO_TAG => '_Save_to_label',
    ];

    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="type", type="string", length=255, nullable=false)
     */
    protected $type;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="User", inversedBy="filters")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="Later", inversedBy="filters", cascade={"persist"})
     * @ORM\JoinColumn(name="later_id", referencedColumnName="id", nullable=false)
     */
    protected $later;

    /**
     * @var ArrayCollection of Feed
     */
    protected $feeds;

    /**
     * @ORM\OneToMany(targetEntity="FilterFeed", mappedBy="filter", cascade={"persist"})
     */
    protected $filterFeeds;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->feeds       = new ArrayCollection();
        $this->filterFeeds = new ArrayCollection();
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Filter
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Filter
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
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
     *
     * @param User $user
     *
     * @return Filter
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
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

    /**
     * Get the later
     *
     * @return Later
     */
    public function getLater()
    {
        return $this->later;
    }

    /**
     * Set the later
     *
     * @param Later $later
     *
     * @return Filter
     */
    public function setLater(Later $later = null)
    {
        $this->later = $later;

        return $this;
    }

    /**
     * Return Filter to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Add feed
     *
     * @param Feed $feed
     *
     * @return Filter
     */
    public function addFeed(Feed $feed)
    {
        $found = false;
        foreach ($this->getFilterFeeds() as $filterFeed) {
            if (!$filterFeed->isDeleted() && $filterFeed->getFeed()->getId() == $feed->getId()) {
                $found = true;
                break;
            }
        }

        if ($found) {
            return $this;
        }

        $filterFeed = new FilterFeed();
        $filterFeed->setFeed($feed);
        $filterFeed->setFilter($this);
        $this->addFilterFeed($filterFeed);
        $this->feeds[] = $feed;

        return $this;
    }

    /**
     * Add feed just for form
     *
     * @param Feed $feed
     *
     * @return Filter
     */
    public function addFeedForm(Feed $feed)
    {
        $this->feeds[] = $feed;

        return $this;
    }

    /**
     * Get feeds
     *
     * @return ArrayCollection of Feed
     */
    public function getFeeds()
    {
        return $this->feeds;
    }

    /**
     * Remove feed
     */
    public function removeFeed(Feed $feed)
    {
        foreach ($this->getFilterFeeds() as $filterFeed) {
            if ($filterFeed->getFeed()->getId() == $feed->getId()) {
                $this->filterFeeds->removeElement($filterFeed);
                $filterFeed->delete();
            }
        }
    }

    /**
     * Add filterFeed
     *
     * @param FilterFeed $filterFeed
     *
     * @return Filter
     */
    public function addFilterFeed(FilterFeed $filterFeed)
    {
        $this->filterFeeds[] = $filterFeed;

        return $this;
    }

    /**
     * Get filter feeds
     *
     * @return FilterFeed[]
     */
    public function getFilterFeeds()
    {
        return $this->filterFeeds;
    }

    /**
     * Empty filter feeds
     *
     * @return ArrayCollection of FilterFeed
     */
    public function emptyFilterFeeds()
    {
        $this->filterFeeds = new ArrayCollection();
    }

    /**
     * Check that name is different
     *
     * @param string $newName
     *
     * @return bool
     */
    public function nameIsDifferent($newName)
    {
        if ($this->getName() != $newName) {
            return true;
        }

        return false;
    }

    /**
     * Check that tag is different
     *
     * @param int $tagId
     *
     * @return bool
     */
    public function tagIsDifferent($tagId)
    {
        if ($this->getLater()->getId() != $tagId) {
            return true;
        }

        return false;
    }
}
