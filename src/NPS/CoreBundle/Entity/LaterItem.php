<?php

namespace NPS\CoreBundle\Entity;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use NPS\CoreBundle\Entity\UserItem;
use NPS\CoreBundle\Entity\Later;

/**
 * LaterItem
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\LaterItemRepository")
 * @ORM\Table(name="later_item")
 * @ORM\HasLifecycleCallbacks
 */
class LaterItem
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
     * @ORM\ManyToOne(targetEntity="UserItem", inversedBy="laterUserItems")
     * @ORM\JoinColumn(name="user_item_id", referencedColumnName="id", nullable=false)
     */
    protected $userItem;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="Later", inversedBy="laterUserItems")
     * @ORM\JoinColumn(name="later_id", referencedColumnName="id", nullable=false)
     */
    protected $later;

    /**
     * @var integer
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="date_add", type="integer")
     */
    protected $dateAdd;

    /**
     * @var int
     * @ORM\Column(name="is_unread", type="boolean", nullable=false)
     */
    protected $isUnread = true;


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
     * Get the userItem
     *
     * @return UserItem
     */
    public function getUserItem()
    {
        return $this->userItem;
    }

    /**
     * Set the userItem
     * @param UserItem $userItem
     */
    public function setUserItem(UserItem $userItem)
    {
        $this->userItem = $userItem;
    }

    /**
     * Get the userItem id
     *
     * @return integer id
     */
    public function getUserItemId()
    {
        if (is_object($this->getUserItem())) {
            $userItemId = $this->getUserItem()->getId();
        } else {
            $userItemId = 0;
        }

        return $userItemId;
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
     * @param Later $later
     */
    public function setLater(Later $later)
    {
        $this->later = $later;
    }

    /**
     * Get the later id
     *
     * @return integer id
     */
    public function getLaterId()
    {
        if (is_object($this->getLater())) {
            $laterId = $this->getLater()->getId();
        } else {
            $laterId = 0;
        }

        return $laterId;
    }

    /**
     * Set dateAdd
     * @param int $dateAdd
     *
     * @return Later
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
     * Set isUnread
     * @param \boolean $isUnread
     *
     * @return LaterItem
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
}