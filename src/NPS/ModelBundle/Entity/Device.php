<?php

namespace NPS\ModelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use NPS\ModelBundle\Entity\User;
use NPS\CoreBundle\Helper\DisplayHelper;

/**
 * Device
 *
 * @ORM\Entity(repositoryClass="NPS\ModelBundle\Repository\DeviceRepository")
 * @ORM\Table(name="device")
 * @ORM\HasLifecycleCallbacks
 */
class Device
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="app_key", type="string", length=255, nullable=false)
     */
    private $appKey;

    /**
     * @var integer
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="date_add", type="integer")
     */
    private $dateAdd;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="User", inversedBy="devices")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;


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
     * Get dateAdd
     *
     * @return int
     */
    public function getDateAdd()
    {
        return $this->dateAdd;
    }

    /**
     * Get added date with human format
     *
     * @return integer
     */
    public function getHumanDateAdd()
    {
        return DisplayHelper::displayDate($this->dateAdd);
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