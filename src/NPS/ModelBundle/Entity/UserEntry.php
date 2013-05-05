<?php

namespace NPS\ModelBundle\Entity;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use NPS\ModelBundle\Entity\Entry;
use NPS\ModelBundle\Entity\User;

/**
 * UserEntry
 *
 * @ORM\Entity(repositoryClass="NPS\ModelBundle\Repository\UserEntryRepository")
 * @ORM\Table(name="user_entry")
 * @ORM\HasLifecycleCallbacks
 */
class UserEntry
{
    /**
     * @var integer
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="Entry", inversedBy="userEntries")
     * @ORM\JoinColumn(name="entry_id", referencedColumnName="id", nullable=false)
     */
    private $entry;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userEntries")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @var int
     * @ORM\Column(name="is_unread", type="boolean")
     */
    private $isUnread;

    /**
     * @var int
     * @ORM\Column(name="is_stared", type="boolean")
     */
    private $isStared;

    /**
     * @var integer
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="date_add", type="integer")
     */
    private $dateAdd;


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
     * Get the entry
     *
     * @return Entry
     */
    public function getEntry()
    {
        return $this->entry;
    }

    /**
     * Set the entry
     * @param Entry $entry
     */
    public function setEntry(Entry $entry)
    {
        $this->entry = $entry;
    }

    /**
     * Get the entry id
     *
     * @return integer id
     */
    public function getEntryId()
    {
        if (is_object($this->getEntry())) {
            $entryId = $this->getEntry()->getId();
        } else {
            $entryId = 0;
        }

        return $entryId;
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
     * @return UserEntry
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
    public function getIsUnread()
    {
        return $this->isUnread;
    }

    /**
     * Set isStared
     * @param \boolean $isStared
     *
     * @return UserEntry
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
    public function getIsStared()
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
