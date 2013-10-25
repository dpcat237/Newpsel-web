<?php

namespace NPS\CoreBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * trait for add Entity enabled
 */
trait EnabledTrait
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="enabled", type="boolean")
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