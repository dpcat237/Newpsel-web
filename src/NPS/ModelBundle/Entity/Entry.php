<?php

namespace NPS\ModelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use NPS\ModelBundle\Entity\Feed;

/**
 * Entry
 *
 * @ORM\Entity(repositoryClass="NPS\ModelBundle\Repository\EntryRepository")
 * @ORM\Table(name="entry")
 * @ORM\HasLifecycleCallbacks
 */
class Entry
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
     * @ORM\Column(name="link", type="string", length=255, nullable=false)
     */
    private $link;

    /**
     * @var string
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    private $content;

    /**
     * @var string
     * @ORM\Column(name="content_hash", type="string", length=255, nullable=false)
     */
    private $contentHash;

    /**
     * @var string
     * @ORM\Column(name="author", type="string", length=255, nullable=true)
     */
    private $author;

    /**
     * @var string
     * @ORM\Column(name="category", type="string", length=255, nullable=true)
     */
    private $category;

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
     * @ORM\ManyToOne(targetEntity="Feed", inversedBy="entries", cascade={"persist"})
     * @ORM\JoinColumn(name="feed_id", referencedColumnName="id", nullable=false)
     */
    private $feed;


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
     *
     * @param string $title
     * @return Entry
     */
    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set link
     *
     * @param string $link
     * @return Entry
     */
    public function setLink($link)
    {
        $this->link = $link;
    
        return $this;
    }

    /**
     * Get link
     *
     * @return string 
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return Entry
     */
    public function setContent($content)
    {
        $this->content = $content;
    
        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set contentHash
     *
     * @param string $contentHash
     * @return Entry
     */
    public function setContentHash($contentHash)
    {
        $this->contentHash = $contentHash;
    
        return $this;
    }

    /**
     * Get contentHash
     *
     * @return string 
     */
    public function getContentHash()
    {
        return $this->contentHash;
    }

    /**
     * Set category
     *
     * @param string $category
     * @return Entry
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set author
     *
     * @param string $author
     * @return Entry
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set dateAdd
     * @param int $dateAdd
     *
     * @return Feed
     */
    public function setDateAdd($dateAdd)
    {
        $this->dateAdd = $dateAdd;

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
     * Get the feed
     *
     * @return Feed
     */
    public function getFeed()
    {
        return $this->feed;
    }

    /**
     * Set the feed.
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
