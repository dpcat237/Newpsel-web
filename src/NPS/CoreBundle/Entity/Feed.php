<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use NPS\CoreBundle\Entity\Item;
use NPS\CoreBundle\Entity\UserFeed;
use NPS\CoreBundle\Helper\DisplayHelper;

/**
 * Feed
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\FeedRepository")
 * @ORM\Table(name="feed")
 * @ORM\HasLifecycleCallbacks
 */
class Feed
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

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
     * @var integer
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="date_add", type="integer")
     */
    protected $dateAdd;

    /**
     * @var integer
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="date_up", type="integer")
     */
    protected $dateUp;

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
     * @ORM\Column(name="date_change", type="integer", nullable=true)
     */
    protected $dateChange;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->userFeeds = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * Set dateAdd
     * @param int $dateAdd
     *
     * @return Feed
     */
    public function setDateAdd($dateAdd = null)
    {
        $dateAddNow = $this->getDateAdd();
        $this->dateAdd = (empty($dateAdd) && empty($dateAddNow))? time() : $dateAdd;

        return $this;
    }

    /**
     * Get dateAdd
     *
     * @return int
     */
    public function getDateAdd()
    {
        return $this->dateAdd;
    }

    /**
     * Set dateUp
     * @param \int $dateUp
     *
     * @return Feed
     */
    public function setDateUp($dateUp = null)
    {
        $this->dateUp = (empty($dateUp))? time() : $dateUp;

        return $this;
    }

    /**
     * Get dateUp
     *
     * @return \int
     */
    public function getDateUp()
    {
        return $this->dateUp;
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
     * Set dateChange
     * @param \int $dateChange
     *
     * @return Feed
     */
    public function setDateChange($dateChange = null)
    {
        $this->dateChange = (empty($dateChange))? time() : $dateChange;

        return $this;
    }

    /**
     * Get dateChange
     *
     * @return \int
     */
    public function getDateChange()
    {
        return $this->dateChange;
    }

    /**
     * Get added date with human format
     *
     * @return integer
     */
    public function getHumanDateSync()
    {
        return DisplayHelper::displayDate($this->dateSync);
    }

}