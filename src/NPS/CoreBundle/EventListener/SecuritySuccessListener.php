<?php
namespace NPS\CoreBundle\EventListener;

use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Event\UserSignUpEvent;
use NPS\CoreBundle\Services\UserWrapper;

class SecuritySuccessListener
{
    /**
     * @var UserWrapper
     */
    private $userWrapper;

    /**
     * @param UserWrapper $userWrapper UserWrapper
     */
    public function __construct(UserWrapper $userWrapper)
    {
        $this->userWrapper = $userWrapper;
    }

    /**
     * Make necessary processes after user signed up
     *
     * @param UserSignUpEvent $event
     */
    public function onUserSignUp(UserSignUpEvent $event)
    {
        $user = $event->getUser();
        if ($user instanceof User && !$this->userWrapper->getCurrentUser() instanceof User) {
            $this->userWrapper->doLogin($user);
        }
    }
}

