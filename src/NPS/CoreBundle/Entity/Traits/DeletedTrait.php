<?php

namespace NPS\CoreBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * trait for add Entity enabled
 */
trait DeletedTrait
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="deleted", type="boolean")
     */
    protected $deleted = false;


    /**
     * Set deleted
     *
     * @param boolean $deleted Deleted value
     *
     * @return AbstractEntity self Object
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }


    /**
     * Delete entity
     *
     * This action is only performed softly. When deleted flag is true, emules hard deletion
     *
     * @return AbstractEntity self Object
     */
    public function delete()
    {
        return $this->setDeleted(true);
    }
}