<?php

namespace NPS\ModelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use NPS\ModelBundle\Entity\Entry;

/**
 * Feed
 *
 * @ORM\Entity(repositoryClass="NPS\ModelBundle\Repository\FeedRepository")
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
    private $id;

    /**
     * @var string
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     * @Assert\NotNull(message={"Write a url"})
     */
    private $url;

    /**
     * @var string
     * @ORM\Column(name="url_hash", type="string", length=255, nullable=false, unique=true)
     */
    private $urlHash;

    /**
     * @var string
     * @ORM\Column(name="website", type="string", length=255, nullable=false)
     */
    private $website;

    /**
     * @var string
     * @ORM\Column(name="language", type="string", length=255, nullable=true)
     */
    private $language;

    /**
     * @var string
     * @ORM\Column(name="favicon", type="string", length=255, nullable=true)
     */
    private $favicon;

    /**
     * @var integer
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="date_add", type="integer")
     */
    private $dateAdd;

    /**
     * @var integer
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="date_up", type="integer")
     */
    private $dateUp;

    /**
     * @var integer
     * @ORM\Column(name="date_sync", type="integer", nullable=true)
     */
    private $dateSync;

    /**
     * @ORM\OneToMany(targetEntity="Entry", mappedBy="feed")
     */
    protected $entries;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->entries = new ArrayCollection();
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
     * Add entries
     * @param Entry $entry
     *
     * @return Feed
     */
    public function addEntry(Entry $entry)
    {
        $this->entries[] = $entry;

        return $this;
    }

    /**
     * Remove entries
     *
     * @param Entry $entry
     */
    public function removeEntry(Entry $entry)
    {
        $this->entries->removeElement($entry);
    }

    /**
     * Get entries
     *
     * @return Collection
     */
    public function getEntries()
    {
        return $this->entries;
    }
}
