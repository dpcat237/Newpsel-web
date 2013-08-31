<?php

namespace NPS\CoreBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use NPS\CoreBundle\Entity\Device;
use NPS\CoreBundle\Entity\AbstractUserFeed;

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
}
