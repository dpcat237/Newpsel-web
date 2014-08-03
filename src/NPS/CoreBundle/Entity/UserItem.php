<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use NPS\CoreBundle\Entity\Item;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Entity\AbstractEntity;

/**
 * UserItem
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\UserItemRepository")
 * @ORM\Table(name="user_item")
 * @ORM\HasLifecycleCallbacks
 */
class UserItem extends AbstractEntity
{
    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="Item", inversedBy="userItems")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false)
     */
    protected $item;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userItems")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var boolean
     * @ORM\Column(name="unread", type="boolean", nullable=false)
     */
    protected $unread = true;

    /**
     * @var boolean
     * @ORM\Column(name="stared", type="boolean", nullable=false)
     */
    protected $stared = false;

    /**
     * @ORM\OneToMany(targetEntity="LaterItem", mappedBy="userItem")
     */
    protected $laterItems;

    /**
     * @var boolean
     * @ORM\Column(name="shared", type="boolean")
     */
    protected $shared = false;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->laterItems = new ArrayCollection();
    }

    /**
     * Get the item
     *
     * @return Item
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Set the item
     * @param Item $item
     */
    public function setItem(Item $item)
    {
        $this->item = $item;
    }

    /**
     * Get the item id
     *
     * @return integer id
     */
    public function getItemId()
    {
        if (is_object($this->getItem())) {
            $itemId = $this->getItem()->getId();
        } else {
            $itemId = 0;
        }

        return $itemId;
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
     * Set isUnread
     * @param \boolean $unread
     *
     * @return UserItem
     */
    public function setUnread($unread)
    {
        $this->unread = $unread;

        return $this;
    }

    /**
     * Get unread
     *
     * @return \int
     */
    public function isUnread()
    {
        return $this->unread;
    }

    /**
     * Set stared
     * @param \boolean $stared
     *
     * @return UserItem
     */
    public function setStared($stared)
    {
        $this->stared = $stared;

        return $this;
    }

    /**
     * Get isStared
     *
     * @return \int
     */
    public function isStared()
    {
        return $this->stared;
    }

    /**
     * Set shared
     * @param \boolean $shared
     *
     * @return UserItem
     */
    public function setShared($shared)
    {
        $this->shared = $shared;

        return $this;
    }
}
