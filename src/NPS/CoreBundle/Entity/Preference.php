<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use NPS\CoreBundle\Entity\AbstractEntity,
    NPS\CoreBundle\Entity\Later;

/**
 * Preference
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\PreferenceRepository")
 * @ORM\Table(name="preference")
 * @ORM\HasLifecycleCallbacks
 */
class Preference extends AbstractEntity
{
    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="Later", inversedBy="preferences")
     * @ORM\JoinColumn(name="shared_later_id", referencedColumnName="id", nullable=true)
     */
    protected $sharedLater;


    /**
     * Get the sharedLater
     *
     * @return Later
     */
    public function getSharedLater()
    {
        return $this->sharedLater;
    }

    /**
     * Set the sharedLater
     * @param Later $sharedLater
     */
    public function setSharedLater(Later $sharedLater)
    {
        $this->sharedLater = $sharedLater;
    }

    /**
     * Get the sharedLater id
     *
     * @return integer id
     */
    public function getSharedLaterId()
    {
        if (is_object($this->getSharedLater())) {
            $sharedLaterId = $this->getSharedLater()->getId();
        } else {
            $sharedLaterId = 0;
        }

        return $sharedLaterId;
    }
}