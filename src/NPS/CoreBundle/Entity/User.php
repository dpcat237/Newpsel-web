<?php

namespace NPS\CoreBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use NPS\CoreBundle\Entity\Device;
use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Entity\UserItem;
use NPS\CoreBundle\Entity\UserFeed;

/**
 * User
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\UserRepository")
 * @ORM\Table(name="user")
 * @ORM\HasLifecycleCallbacks
 */
class User extends AbstractUser
{
    /**
     * @ORM\OneToMany(targetEntity="Device", mappedBy="user")
     */
    protected $devices;

    /**
     * @ORM\OneToMany(targetEntity="UserItem", mappedBy="user")
     */
    protected $userItems;

    /**
     * @ORM\OneToMany(targetEntity="UserFeed", mappedBy="user")
     */
    protected $userFeeds;

    /**
     * @var boolean
     * @ORM\Column(name="registered", type="boolean")
     */
    protected $registered = false;

    /**
     * @var boolean
     * @ORM\Column(name="subscribed", type="boolean")
     */
    protected $subscribed = false;


    /**
     * @ORM\OneToMany(targetEntity="Later", mappedBy="user")
     */
    protected $laters;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->devices = new ArrayCollection();
        $this->userItems = new ArrayCollection();
        $this->userFeeds = new ArrayCollection();
        $this->laters = new ArrayCollection();
    }

    /**
     * Add devices
     * @param Device $device
     *
     * @return Feed
     */
    public function addDevice(Device $device)
    {
        $this->devices[] = $device;

        return $this;
    }

    /**
     * Remove devices
     *
     * @param Device $device
     */
    public function removeDevice(Device $device)
    {
        $this->devices->removeElement($device);
    }

    /**
     * Get devices
     *
     * @return Collection
     */
    public function getDevices()
    {
        return $this->devices;
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
     * Set registered
     * @param \boolean $registered
     *
     * @return User
     */
    public function setRegistered($registered)
    {
        $this->registered = $registered;

        return $this;
    }

    /**
     * Is registered
     *
     * @return \int
     */
    public function isRegistered()
    {
        return $this->registered;
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
