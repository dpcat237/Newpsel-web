<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use NPS\CoreBundle\Entity\Traits\EnabledTrait;

/**
 * Filter
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\FilterRepository")
 * @ORM\Table(name="filter")
 * @ORM\HasLifecycleCallbacks
 */
class Filter extends AbstractEntity
{
    use EnabledTrait;

    /**
     * Types of filters
     */
    const TO_LABEL = 'to.label';

    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="type", type="string", length=255, nullable=false)
     */
    protected $type;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="User", inversedBy="filters")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="Later")
     * @ORM\JoinColumn(name="later_id", referencedColumnName="id", nullable=false)
     */
    protected $later;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->filterItems = new ArrayCollection();
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Filter
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Filter
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
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
     * Return Filter to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
