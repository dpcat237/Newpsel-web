<?php
namespace NPS\CoreBundle\Services;

use NPS\CoreBundle\Entity\User;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * UserWrapper
 */
class UserWrapper
{
    /**
     * @var SecurityContext
     */
    private $security;

    /**
     * @var User
     */
    private $user;


    /**
     * @param SecurityContext $security SecurityContext
     */
    public function __construct(SecurityContext $security)
    {
        $this->security = $security;
    }

    /**
     * Set logged user
     */
    public function setCurrentUser()
    {
        if (!$this->user instanceof User && $this->security->getToken() instanceof UsernamePasswordToken) {
            $this->user = $this->security->getToken()->getUser();
        }
    }

    /**
     * Get current user
     *
     * @return User
     */
    public function getCurrentUser()
    {
        return $this->user;
    }
}
