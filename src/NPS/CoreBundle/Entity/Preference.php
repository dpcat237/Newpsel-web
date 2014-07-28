<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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
     * @ORM\OneToOne(targetEntity="User", mappedBy="preference")
     **/
    protected $user;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="Later", inversedBy="preferences")
     * @ORM\JoinColumn(name="read_later_id", referencedColumnName="id", nullable=true)
     */
    protected $readLater;


    /**
     * Get the readLater
     *
     * @return Later
     */
    public function getReadLater()
    {
        return $this->readLater;
    }

    /**
     * Set the readLater
     * @param Later $readLater
     */
    public function setReadLater(Later $readLater)
    {
        $this->readLater = $readLater;
    }

    /**
     * Get the readLater id
     *
     * @return integer id
     */
    public function getReadLaterId()
    {
        if (is_object($this->getReadLater())) {
            $readLaterId = $this->getReadLater()->getId();
        } else {
            $readLaterId = 0;
        }

        return $readLaterId;
    }
}