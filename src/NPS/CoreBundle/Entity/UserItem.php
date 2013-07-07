<?php

namespace NPS\CoreBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use NPS\CoreBundle\Entity\Item;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Entity\LaterItem;

/**
 * UserItem
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\UserItemRepository")
 * @ORM\Table(name="user_item")
 * @ORM\HasLifecycleCallbacks
 */
class UserItem
{
    /**
     * @var integer
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

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
     * @var int
     * @ORM\Column(name="is_unread", type="boolean", nullable=false)
     */
    protected $isUnread = false;

    /**
     * @var int
     * @ORM\Column(name="is_stared", type="boolean", nullable=false)
     */
    protected $isStared = false;

    /**
     * @var integer
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="date_add", type="integer")
     */
    protected $dateAdd;

    /**
     * @ORM\OneToMany(targetEntity="LaterItem", mappedBy="userItems")
     */
    protected $laterItems;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->laterItems = new ArrayCollection();
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
     * @param \boolean $isUnread
     *
     * @return UserItem
     */
    public function setIsUnread($isUnread)
    {
        $this->isUnread = $isUnread;

        return $this;
    }

    /**
     * Get isEnabled
     *
     * @return \int
     */
    public function isUnread()
    {
        return $this->isUnread;
    }

    /**
     * Get isEnabled
     *
     * @return \int
     */
    public function getIsUnread()
    {
        return $this->isUnread;
    }

    /**
     * Set isStared
     * @param \boolean $isStared
     *
     * @return UserItem
     */
    public function setIsStared($isStared)
    {
        $this->isStared = $isStared;

        return $this;
    }

    /**
     * Get isStared
     *
     * @return \int
     */
    public function isStared()
    {
        return $this->isStared;
    }

    /**
     * Set dateAdd
     * @param int $dateAdd
     *
     * @return User
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
}
