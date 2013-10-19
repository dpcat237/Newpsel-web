<?php

namespace NPS\CoreBundle\Entity\Traits;

/**
 * trait for add Entity enabled funct
 */
trait EnabledTrait
{
    /**
     * @var boolean
     *
     * @\Doctrine\ORM\Mapping\Column(name="enabled", type="boolean")
     */
    protected $enabled = true;


    /**
     * Set isEnabled
     *
     * @param boolean $enabled enabled value
     *
     * @return Object self Object
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get if entity is enabled
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }
}