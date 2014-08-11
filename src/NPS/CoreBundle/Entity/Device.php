<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Entity\AbstractEntity;

/**
 * Device
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\DeviceRepository")
 * @ORM\Table(name="device")
 * @ORM\HasLifecycleCallbacks
 */
class Device extends AbstractEntity
{
    /**
     * @var string
     * @ORM\Column(name="app_key", type="string", length=255, nullable=false)
     */
    protected $appKey;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="User", inversedBy="devices")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var string
     * @ORM\Column(name="gcm_id", type="string", length=255, nullable=true)
     */
    protected $gcmId;


    /**
     * Set appKey
     * @param string $appKey
     *
     * @return Device
     */
    public function setAppKey($appKey)
    {
        $this->appKey = $appKey;

        return $this;
    }

    /**
     * Get app key
     *
     * @return string 
     */
    public function getAppKey()
    {
        return $this->appKey;
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
     * Set GCM id
     *
     * @param string $gcmId
     *
     * @return Device
     */
    public function setGcmId($gcmId)
    {
        $this->gcmId = $gcmId;

        return $this;
    }

    /**
     * Get GCM ID
     *
     * @return string
     */
    public function getGcmId()
    {
        return $this->gcmId;
    }
}