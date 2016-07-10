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
    const PREMIUM_NO = 'no';
    const PREMIUM_SUBSCRIPTION_NORMAL = 'subscription_normal';

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
     * @var string
     * @ORM\Column(name="premium", type="string", length=255, nullable=false)
     */
    protected $premium = self::PREMIUM_NO;

    /**
     * Get dictation tag
     *
     * @return Later
     */
    public function getDictationTag()
    {
        return $this->readLater;
    }

    /**
     * Set dictation tag
     *
     * @param Later $readLater
     *
     * @return $this
     */
    public function setDictationTag(Later $readLater)
    {
        $this->readLater = $readLater;

        return $this;
    }

    /**
     * Get dictation tag id
     *
     * @return integer id
     */
    public function getDictationTagId()
    {
        $readLaterId = 0;
        if (is_object($this->getDictationTag())) {
            $readLaterId = $this->getDictationTag()->getId();
        }

        return $readLaterId;
    }

    /**
     * Get premium type
     *
     * @return string
     */
    public function getPremium()
    {
        return $this->premium;
    }

    /**
     * Set premium type
     *
     * @param $premiumType
     *
     * @return $this
     */
    public function setPremium($premiumType)
    {
        $this->premium = $premiumType;

        return $this;
    }
}
