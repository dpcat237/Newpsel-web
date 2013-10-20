<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use NPS\CoreBundle\Entity\UserItem;
use NPS\CoreBundle\Entity\UserFeed;
use NPS\CoreBundle\Entity\AbstractUser;

/**
 * AbstractUserFeed
 *
 */
abstract class AbstractUserFeed extends AbstractUser
{
    /**
     * @ORM\OneToMany(targetEntity="UserItem", mappedBy="user")
     */
    protected $userItems;

    /**
     * @ORM\OneToMany(targetEntity="UserFeed", mappedBy="user")
     */
    protected $userFeeds;

    /**
     * @ORM\OneToMany(targetEntity="Later", mappedBy="user")
     */
    protected $laters;

    /**
     * @var boolean
     * @ORM\Column(name="subscribed", type="boolean")
     */
    protected $subscribed = false;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->userItems = new ArrayCollection();
        $this->userFeeds = new ArrayCollection();
        $this->laters = new ArrayCollection();
    }

    /**
     * Add userItem
     * @param UserItem $userItem
     *
     * @return User
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
     * Add userFeed
     * @param UserFeed $userFeed
     *
     * @return User
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
     * Get userFeeds
     *
     * @return Collection
     */
    public function getUserFeeds()
    {
        return $this->userFeeds;
    }

    /**
     * Set subscribed
     * @param \boolean $subscribed
     *
     * @return User
     */
    public function setSubscribed($subscribed)
    {
        $this->subscribed = $subscribed;

        return $this;
    }

    /**
     * Is subscribed
     *
     * @return \int
     */
    public function isSubscribed()
    {
        return $this->subscribed;
    }

    /**
     * Add Later
     * @param Later $later
     *
     * @return User
     */
    public function addLater(Later $later)
    {
        $this->laters[] = $later;

        return $this;
    }

    /**
     * Remove userFeed
     *
     */
    public function removeLater(Later $later)
    {
        $this->laters->removeElement($later);
    }

    /**
     * Get Laters
     *
     * @return Collection
     */
    public function getLaters()
    {
        return $this->laters;
    }
}
