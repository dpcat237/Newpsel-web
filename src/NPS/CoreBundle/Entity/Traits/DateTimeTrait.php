<?php

namespace NPS\CoreBundle\Entity\Traits;

use Gedmo\Mapping\Annotation as Gedmo;
use NPS\CoreBundle\Helper\DisplayHelper;

/**
 * trait for DateTime common variables and methods
 */
trait DateTimeTrait
{
    /**
     * @var integer
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="date_add", type="integer")
     */
    protected $dateAdd;

    /**
     * @var integer
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="date_up", type="integer")
     */
    protected $dateUp;


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
        return DisplayHelper::displayDate($this->getDateAdd());
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
}