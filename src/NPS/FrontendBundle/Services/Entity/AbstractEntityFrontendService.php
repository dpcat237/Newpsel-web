<?php

namespace NPS\FrontendBundle\Services\Entity;

use NPS\CoreBundle\Services\NotificationManager;
use NPS\CoreBundle\Services\UserWrapper;

/**
 * Class AbstractEntityFrontendService
 *
 * @package NPS\FrontendBundle\Services\Entity
 */
abstract class AbstractEntityFrontendService
{
    use EntityFrontendServiceTrait;

    /** @var UserWrapper */
    protected $userWrapper;

    /**
     * AbstractEntityService constructor.
     *
     * @param UserWrapper         $userWrapper
     * @param NotificationManager $notification
     */
    public function __construct(UserWrapper $userWrapper, NotificationManager $notification)
    {
        $this->userWrapper  = $userWrapper;
        $this->notification = $notification;
    }
}
