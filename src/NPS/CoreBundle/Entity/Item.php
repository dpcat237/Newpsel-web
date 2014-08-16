<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Entity\UserItem;
use NPS\CoreBundle\Entity\AbstractEntity;

/**
 * Item
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\ItemRepository")
 * @ORM\Table(name="item",indexes={@ORM\Index(name="search_idx", columns={"link"})})
 * @ORM\HasLifecycleCallbacks
 */
class Item extends AbstractEntity
{
    /**
     * @var string
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected $title;

    /**
     * @var string
     * @ORM\Column(name="link", type="string", length=255, nullable=false)
     */
    protected $link;

    /**
     * @var string
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    protected $content;

    /**
     * @var string
     * @ORM\Column(name="content_hash", type="string", length=255, nullable=false)
     */
    protected $contentHash;

    /**
     * @var string
     * @ORM\Column(name="author", type="string", length=255, nullable=true)
     */
    protected $author;

    /**
     * @var string
     * @ORM\Column(name="category", type="string", length=255, nullable=true)
     */
    protected $category;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="Feed", inversedBy="items", cascade={"persist"})
     * @ORM\JoinColumn(name="feed_id", referencedColumnName="id", nullable=true)
     */
    protected $feed;

    /**
     * @ORM\OneToMany(targetEntity="UserItem", mappedBy="item")
     */
    protected $userItems;

    /**
     * @var string
     * @ORM\Column(name="language", type="string", length=2, nullable=true)
     */
    protected $language;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->userItems = new ArrayCollection();
    }

    /**
     * Set title
     * @param string $title
     *
     * @return Item
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
     * @param string $link
     *
     * @return Item
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
     * @param string $content
     *
     * @return Item
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
     * @param string $contentHash
     *
     * @return Item
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
     * @param string $category
     *
     * @return Item
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
     * @param string $author
     *
     * @return Item
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

    /**
     * Add userItem
     * @param UserItem $userItem
     *
     * @return Item
     */
    public function addUserItem(UserItem $userItem)
    {
        $this->userItems[] = $userItem;

        return $this;
    }

    /**
     * Remove userItem
     *
     */
    public function removeUserItem(UserItem $userItem)
    {
        $this->userItems->removeElement($userItem);
    }

    /**
     * Get userItems
     *
     * @return Collection
     */
    public function getUserItems()
    {
        return $this->userItems;
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
}
