<?php
namespace NPS\CoreBundle\EventListener;

use NPS\CoreBundle\Event\UserSignUpEvent;
use NPS\CoreBundle\Services\Entity\UserService;

class UserListener
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * @param UserService $userService User Service
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Make necessary processes after user signed up
     *
     * @param UserSignUpEvent $event
     */
    public function onUserSignup(UserSignUpEvent $event)
    {
        $this->userService->setPreferenceNewUser($event->getUser());
    }
}

