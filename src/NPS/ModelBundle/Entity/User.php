<?php

namespace NPS\ModelBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use NPS\ModelBundle\Entity\Device;
use NPS\ModelBundle\Entity\Feed;
use NPS\ModelBundle\Entity\UserEntry;
use NPS\ModelBundle\Entity\UserFeed;


/**
 * User
 *
 * @ORM\Entity(repositoryClass="NPS\ModelBundle\Repository\UserRepository")
 * @ORM\Table(name="user")
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity(fields="username", message="Sorry, this username is not available or allowed")
 * @UniqueEntity(fields="username", message="Sorry, this username is not available or allowed", groups={"registration"})
 * @UniqueEntity(fields="email", message="Sorry, this email is not available or allowed")
 */
class User implements UserInterface
{
    /**
     * @var bigint $id
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="username", type="string", length=255, nullable=false, unique=true)
     * @Assert\NotNull(message={"Write an username"})
     */
    private $username;

    /**
     * @var string
     * @ORM\Column(name="email", type="string", length=255, nullable=false, unique=true)
     * @Assert\NotNull(message={"Write an email"})
     */
    private $email;

    /**
     * @var string
     * @ORM\Column(name="password", type="string", length=255, nullable=false)
     * @Assert\NotNull(groups={"registration"})
     */
    private $password;

    /**
     * @var int
     * @ORM\Column(name="is_enabled", type="boolean")
     */
    private $isEnabled;

    /**
     * @var integer
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="date_add", type="integer")
     */
    private $dateAdd;

    /**
     * @var integer
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="date_up", type="integer")
     */
    private $dateUp;

    /**
     * @ORM\OneToMany(targetEntity="Device", mappedBy="feed")
     */
    protected $devices;

    /**
     * @ORM\OneToMany(targetEntity="UserEntry", mappedBy="entry", cascade={"persist"})
     */
    protected $userEntries;

    /**
     * @ORM\OneToMany(targetEntity="UserFeed", mappedBy="feed", cascade={"persist"})
     */
    protected $userFeeds;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->devices = new ArrayCollection();
        $this->userEntries = new ArrayCollection();
        $this->userFeeds = new ArrayCollection();
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
    public function getEntries()
    {
        return $this->devices;
    }

    /**
     * Add userEntry
     * @param UserEntry $userEntry
     *
     * @return User
     */
    public function addUserEntry(UserEntry $userEntry)
    {
        $this->userEntries[] = $userEntry;

        return $this;
    }

    /**
     * Remove userEntry
     *
     */
    public function removeUserEntry(UserEntry $userEntry)
    {
        $this->userEntries->removeElement($userEntry);
    }

    /**
     * Get userEntries
     *
     * @return Collection
     */
    public function getUserEntries()
    {
        return $this->userEntries;
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
     * Add feed
     * @param Feed $feed
     *
     * @return User
     */
    public function addFeed(Feed $feed)
    {
        $userFeed = new UserFeed();
        $userFeed->setUser($this);
        $userFeed->setFeed($feed);

        return $this->addUserFeed($userFeed);
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
     * Check if user already is subscribed to this feed
     * @param $feedId
     *
     * @return Boolean
     */
    public function checkFeedExists($feedId)
    {
        foreach ($this->getUserFeeds() as $feed) {
            if ($feedId == $feed->getFeedId()) {
                return true;
            }
        }

        return false;
    }
}
