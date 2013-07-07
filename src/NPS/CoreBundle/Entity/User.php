<?php

namespace NPS\CoreBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
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
//TODO: when will be possible form->getErrorsAsArray (https://github.com/symfony/symfony/pull/7512 / https://github.com/symfony/symfony/issues/7205)
//@UniqueEntity(fields="username", message="Sorry, this username is not available or allowed")
//@UniqueEntity(fields="username", message="Sorry, this username is not available or allowed", groups={"registration"})
//@UniqueEntity(fields="email", message="Sorry, this email is not available or allowed")
class User implements UserInterface
{
    /**
     * @var bigint $id
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="username", type="string", length=255, nullable=true, unique=true)
     * @Assert\NotNull(message={"Write an username"})
     */
    protected $username;

    /**
     * @var string
     * @ORM\Column(name="email", type="string", length=255, nullable=false, unique=true)
     * @Assert\NotNull(message={"Write an email"})
     */
    protected $email;

    /**
     * @var string
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     * @Assert\NotNull(groups={"registration"})
     */
    protected $password;

    /**
     * @var boolean
     * @ORM\Column(name="is_enabled", type="boolean")
     */
    protected $isEnabled = false;

    /**
     * @var integer
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="date_add", type="integer")
     */
    protected $dateAdd;

    /**
     * @var integer
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="date_up", type="integer")
     */
    protected $dateUp;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user
     * @param string $username
     *
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get user
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set email
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get user
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set password
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        if ($password) {
            $this->password = $password;
        }

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set isEnabled
     * @param \boolean $isEnabled
     *
     * @return User
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * Get isEnabled
     *
     * @return \int
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
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

    /**
     * Set dateUp
     * @param \int $dateUp
     *
     * @return User
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
     * Part of UserInterface. Dummy
     *
     * @return string ""
     */
    public function getSalt()
    {
        return "";
    }

    /**
     * Part of UserInterface.
     *
     * Get the roles this user has. ROLE_USER by default and at least in the
     * first implementation, as we only want to discriminate between logged
     * and not logged
     *
     * @return array with the user roles
     */
    public function getRoles()
    {
        return array('ROLE_USER');
    }

    /**
     * Part of UserInterface.
     *
     * Checks if $user is the same user and this instance
     * @param UserInterface $user
     *
     * @return boolean if the user is the same
     */
    public function equals(UserInterface $user)
    {
        return $user->getId() === $this->getId();
    }

    /**
     * Part of UserInterface.
     *
     * Dummy function, returns empty string
     *
     * @return string
     */
    public function eraseCredentials()
    {
        return "";
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
     * @param UserFeed $later
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
