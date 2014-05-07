<?php
namespace NPS\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use NPS\CoreBundle\Entity\User;

/**
 * Event fired when a customer signs up
 *
 * This event send an email to customer
 */
class UserSignUpEvent extends Event
{
    /**
     * @var User
     */
    private $user;

    /**
     * construct method
     *
     * @param User $user the user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get User
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
