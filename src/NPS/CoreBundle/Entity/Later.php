<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use NPS\CoreBundle\Entity\LaterItem;
use NPS\CoreBundle\Entity\AbstractEntity;
use NPS\CoreBundle\Entity\Traits\EnabledTrait;

/**
 * Later
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\LaterRepository")
 * @ORM\Table(name="`later`")
 * @ORM\HasLifecycleCallbacks
 */
class Later extends AbstractEntity
{
    use EnabledTrait;

    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @ORM\OneToMany(targetEntity="LaterItem", mappedBy="later", cascade={"persist", "remove"})
     */
    protected $laterItems;

    /**
     * @ORM\OneToMany(targetEntity="Preference", mappedBy="sharedLater")
     **/
    protected $preferences;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="User", inversedBy="laters")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @ORM\OneToMany(targetEntity="Filter", mappedBy="later")
     */
    protected $filters;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_basic", type="boolean", nullable=true)
     */
    protected $basic = false;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->filters = new ArrayCollection();
        $this->laterItems = new ArrayCollection();
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Later
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

    /**
     * Add laterItem
     * @param LaterItem $laterItem
     *
     * @return Feed
     */
    public function addLaterItem(LaterItem $laterItem)
    {
        $this->laterItems[] = $laterItem;

        return $this;
    }

    /**
     * Remove items
     *
     * @param LaterItem $laterItem
     */
    public function removeLaterItem(LaterItem $laterItem)
    {
        $this->laterItems->removeElement($laterItem);
    }

    /**
     * Get items
     *
     * @return Collection
     */
    public function getLaterItems()
    {
        return $this->laterItems;
    }

    /**
     * Add filter
     *
     * @param Filter $filter
     *
     * @return Feed
     */
    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Remove filter
     *
     * @param Filter $filter
     */
    public function removeFilter(Filter $filter)
    {
        $this->laterItems->removeElement($filter);
    }

    /**
     * Get items
     *
     * @return Collection
     */
    public function getFilters()
    {
        return $this->laterItems;
    }

    /**
     * Set is basic
     *
     * @param boolean $basic
     *
     * @return Feed
     */
    public function setBasic($basic)
    {
        $this->basic = $basic;

        return $this;
    }

    /**
     * Get if later is basic and can't be deleted
     *
     * @return boolean
     */
    public function isBasic()
    {
        return $this->basic;
    }

    /**
     * Return Later to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
