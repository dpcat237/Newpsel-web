<?php

namespace NPS\CoreBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use NPS\CoreBundle\Entity\UserItem;
use NPS\CoreBundle\Entity\Later;
use NPS\CoreBundle\Entity\AbstractEntity;

/**
 * LaterItem
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\LaterItemRepository")
 * @ORM\Table(name="later_item")
 * @ORM\HasLifecycleCallbacks
 */
class LaterItem extends AbstractEntity
{
    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="UserItem", inversedBy="laterItems")
     * @ORM\JoinColumn(name="user_item_id", referencedColumnName="id", nullable=false)
     */
    protected $userItem;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="Later", inversedBy="laterItems")
     * @ORM\JoinColumn(name="later_id", referencedColumnName="id", nullable=false)
     */
    protected $later;

    /**
     * @var boolean
     * @ORM\Column(name="unread", type="boolean", nullable=false)
     */
    protected $unread = true;


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
     * Set isUnread
     * @param \boolean $unread
     *
     * @return LaterItem
     */
    public function setUnread($unread)
    {
        $this->unread = $unread;

        return $this;
    }

    /**
     * Get isUnread
     *
     * @return \int
     */
    public function isUnread()
    {
        return $this->unread;
    }
}
