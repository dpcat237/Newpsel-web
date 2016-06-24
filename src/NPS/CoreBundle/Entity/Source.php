<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * Source
 *
 * @ORM\Entity()
 * @ORM\Table(name="source")
 * @ORM\HasLifecycleCallbacks
 */
class Source extends AbstractEntity
{
    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="web", type="string", length=255, nullable=false)
     */
    protected $web;

    /**
     * @var string
     * @ORM\Column(name="feed_url", type="string", length=255, nullable=false)
     */
    protected $feedUrl;

    /**
     * @var SourceCategory[]|PersistentCollection
     * @ORM\ManyToMany(targetEntity="SourceCategory", inversedBy="sources")
     * @ORM\JoinTable(name="sources_categories")
     */
    private $categories;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return $this
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
     * Set web
     *
     * @param string $web
     *
     * @return $this
     */
    public function setWeb($web)
    {
        $this->web = $web;

        return $this;
    }

    /**
     * Get web
     *
     * @return string
     */
    public function getWeb()
    {
        return $this->web;
    }

    /**
     * Set feed url
     *
     * @param string $feedUrl
     *
     * @return $this
     */
    public function setFeedUrl($feedUrl)
    {
        $this->feedUrl = $feedUrl;

        return $this;
    }

    /**
     * Get feed url
     *
     * @return string
     */
    public function getFeedUrl()
    {
        return $this->feedUrl;
    }

    /**
     * Get source categories.
     *
     * @return PersistentCollection|SourceCategory[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Add source category
     *
     * @param SourceCategory $category
     *
     * @return $this
     */
    public function addCategory(SourceCategory $category)
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    /**
     * Remove source category from staff collection.
     *
     * @param SourceCategory $category
     *
     * @return $this
     */
    public function removeCategory(SourceCategory $category)
    {
        if ($this->categories->contains($category)) {
            $this->categories->removeElement($category);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
