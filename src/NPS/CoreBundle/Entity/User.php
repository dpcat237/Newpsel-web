<?php

namespace NPS\CoreBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use NPS\CoreBundle\Entity\AbstractUserFeed,
    NPS\CoreBundle\Entity\Device,
    NPS\CoreBundle\Entity\Preference;

/**
 * User
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\UserRepository")
 * @ORM\Table(name="user")
 * @ORM\HasLifecycleCallbacks
 */
class User extends AbstractUserFeed
{
    /**
     * @ORM\OneToMany(targetEntity="Device", mappedBy="user")
     */
    protected $devices;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="Preference", inversedBy="users")
     * @ORM\JoinColumn(name="preference_id", referencedColumnName="id", nullable=true)
     */
    protected $preference;

    /**
     * @var boolean
     * @ORM\Column(name="registered", type="boolean")
     */
    protected $registered = false;


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->devices = new ArrayCollection();
    }

    /**
     * Add devices
     * @param Device $device
     *
     * @return User
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
     * Get the preference
     *
     * @return Preference
     */
    public function getPreference()
    {
        return $this->preference;
    }

    /**
     * Set the preference
     * @param Preference $preference
     */
    public function setPreference(Preference $preference)
    {
        $this->preference = $preference;
    }

    /**
     * Get the preference id
     *
     * @return integer id
     */
    public function getPreferenceId()
    {
        if (is_object($this->getPreference())) {
            $preferenceId = $this->getPreference()->getId();
        } else {
            $preferenceId = 0;
        }

        return $preferenceId;
    }
}
