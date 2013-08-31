<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Entity\Traits\DateTimeTrait;

/**
 * Device
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\DeviceRepository")
 * @ORM\Table(name="device")
 * @ORM\HasLifecycleCallbacks
 */
class Device
{
    use DateTimeTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set appKey
     * @param string $appKey
     *
     * @return Item
     */
    public function setAppKey($appKey)
    {
        $this->appKey = $appKey;

        return $this;
    }

    /**
     * Get title
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
}