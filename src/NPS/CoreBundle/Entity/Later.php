<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;
use NPS\CoreBundle\Entity\LaterItem;

/**
 * Later
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\LaterRepository")
 * @ORM\Table(name="`later`")
 * @ORM\HasLifecycleCallbacks
 */
class Later
{
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
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

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
     * @ORM\OneToMany(targetEntity="LaterItem", mappedBy="later", cascade={"persist", "remove"})
     */
    protected $laterItems;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="User", inversedBy="laters")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->laterItems = new ArrayCollection();
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
     * Set name
     *
     * @param string $name
     * @return Later
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
     * Set dateAdd
     * @param int $dateAdd
     *
     * @return Feed
     */
    public function setDateAdd($dateAdd)
    {
        $this->dateAdd = $dateAdd;

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
     * Get added date with human format
     *
     * @return integer
     */
    public function getHumanDateAdd()
    {
        return DisplayHelper::displayDate($this->dateAdd);
    }

    /**
     * Set dateUp
     * @param \int $dateUp
     *
     * @return Feed
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
     * Add laterItem
     * @param LaterItem $laterItem
     *
     * @return Feed
     */
    public function addLaterItem(LaterItem $laterItem)
    {
        $this->laterItems[] = $laterItem;

        return $this;
    }

    /**
     * Remove items
     *
     * @param LaterItem $laterItem
     */
    public function removeLaterItem(LaterItem $laterItem)
    {
        $this->laterItems->removeElement($laterItem);
    }

    /**
     * Get items
     *
     * @return Collection
     */
    public function getLaterItems()
    {
        return $this->laterItems;
    }
}
