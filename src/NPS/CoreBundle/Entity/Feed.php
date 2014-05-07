<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use NPS\CoreBundle\Entity\Traits\EnabledTrait;
use NPS\CoreBundle\Helper\FormatHelper;

/**
 * Feed
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\FeedRepository")
 * @ORM\Table(name="feed")
 * @ORM\HasLifecycleCallbacks
 */
class Feed extends AbstractEntity
{
    use EnabledTrait;

    /**
     * @var string
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected $title;

    /**
     * @var string
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     * @Assert\NotNull(message={"Write a url"})
     */
    protected $url;

    /**
     * @var string
     * @ORM\Column(name="url_hash", type="string", length=255, nullable=false, unique=true)
     */
    protected $urlHash;

    /**
     * @var string
     * @ORM\Column(name="website", type="string", length=255, nullable=false)
     */
    protected $website;

    /**
     * @var string
     * @ORM\Column(name="language", type="string", length=255, nullable=true)
     */
    protected $language;

    /**
     * @var string
     * @ORM\Column(name="favicon", type="string", length=255, nullable=true)
     */
    protected $favicon;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_crawling", type="boolean", nullable=true)
     */
    protected $crawling = false;

    /**
     * @ORM\OneToMany(targetEntity="Item", mappedBy="feed")
     */
    protected $items;

    /**
     * @ORM\OneToMany(targetEntity="UserFeed", mappedBy="feed")
     */
    protected $userFeeds;

    /**
     * @var integer
     * @ORM\Column(name="date_sync", type="integer", nullable=true)
     */
     protected $dateSync;

    /**
     * @var integer
     * @ORM\Column(name="sync_interval", type="integer")
     */
    protected $syncInterval = 1800;

    /**
     * @var integer
     * @ORM\OneToMany(targetEntity="FeedHistory", mappedBy="feed")
     */
    protected $history;

    /**
     * @ORM\OneToMany(targetEntity="FilterFeed", mappedBy="feed", cascade={"persist"})
     */
    protected $filterFeeds;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->filterfeeds = new ArrayCollection();
        $this->history = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->userFeeds = new ArrayCollection();
    }

    /**
     * Set title
     * @param string $title
     *
     * @return Feed
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
     * Set url
     * @param string $url
     *
     * @return Feed
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set urlHash
     * @param string $urlHash
     *
     * @return Feed
     */
    public function setUrlHash($urlHash)
    {
        $this->urlHash = $urlHash;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrlHash()
    {
        return $this->urlHash;
    }

    /**
     * Set website
     * @param string $website
     *
     * @return Feed
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website
     *
     * @return string 
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set language
     * @param string $language
     *
     * @return Feed
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get website
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set favicon
     * @param string $favicon
     *
     * @return Feed
     */
    public function setFavicon($favicon)
    {
        $this->favicon = $favicon;

        return $this;
    }

    /**
     * Get website
     *
     * @return string
     */
    public function getFavicon()
    {
        return $this->favicon;
    }

    /**
     * Set is crawling
     *
     * @param boolean $crawling
     *
     * @return Feed
     */
    public function setCrawling($crawling)
    {
        $this->crawling = $crawling;

        return $this;
    }

    /**
     * Get if feed's items must be crawled
     *
     * @return boolean
     */
    public function isCrawling()
    {
        return $this->crawling;
    }

    /**
     * Add items
     * @param Item $item
     *
     * @return Feed
     */
    public function addItem(Item $item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Remove items
     *
     * @param Item $item
     */
    public function removeItem(Item $item)
    {
        $this->items->removeElement($item);
    }

    /**
     * Get items
     *
     * @return Collection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Add userFeed
     * @param UserFeed $userFeed
     *
     * @return Feed
     */
    public function addUserFeed(UserFeed $userFeed)
    {
        $this->userFeeds[] = $userFeed;

        return $this;
    }

    /**
     * Remove userFeed
     *
     */
    public function removeUserFeed(UserFeed $userFeed)
    {
        $this->userFeeds->removeElement($userFeed);
    }

    /**
     * Get userItems
     *
     * @return Collection
     */
    public function getUserFeeds()
    {
        return $this->userFeeds;
    }

    /**
     * Set dateSync
     * @param \int $dateSync
     *
     * @return Feed
     */
    public function setDateSync($dateSync = null)
    {
        $this->dateSync = (empty($dateSync))? time() : $dateSync;

        return $this;
    }

    /**
     * Get dateSync
     *
     * @return \int
     */
    public function getDateSync()
    {
        return $this->dateSync;
    }

    /**
     * Set syncInterval
     * @param \int $syncInterval
     *
     * @return Feed
     */
    public function setSyncInterval($syncInterval)
    {
        $this->syncInterval = $syncInterval;

        return $this;
    }

    /**
     * Get syncInterval
     *
     * @return \int
     */
    public function getSyncInterval()
    {
        return $this->syncInterval;
    }

    /**
     * Get added date with human format
     *
     * @return integer
     */
    public function getHumanDateSync()
    {
        return FormatHelper::displayDate($this->dateSync);
    }

    /**
     * Add history
     * @param FeedHistory $history
     *
     * @return Feed
     */
    public function addHistory(FeedHistory $history)
    {
        $this->history[] = $history;

        return $this;
    }

    /**
     * Get history
     *
     * @return Collection
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * Return Feed to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->title;
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
     * Get feeds
     *
     * @return ArrayCollection of FilterFeed
     */
    public function getFilterFeeds()
    {
        return $this->filterFeeds;
    }

    /**
     * Remove FilterFeed
     *
     */
    public function removeFilterFeed(FilterFeed $filterFeed)
    {
        $this->filterFeeds->removeElement($filterFeed);
    }
}
