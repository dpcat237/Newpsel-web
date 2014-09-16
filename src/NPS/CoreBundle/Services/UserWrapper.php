<?php
namespace NPS\CoreBundle\Services;

use NPS\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;

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
     * @var Session
     */
    private $session;

    /**
     * @var User
     */
    private $user;


    /**
     * @param SecurityContext $security SecurityContext
     * @param Session         $session Session
     */
    public function __construct(SecurityContext $security, Session $session)
    {
        $this->security = $security;
        $this->session = $session;
    }

    /**
     * Do login manually setting user to session
     *
     * @param User $user
     *
     * @return User|null
     */
    public function doLogin(User $user)
    {
        $ok = false;

        //create new token
        $token = new UsernamePasswordToken($user, null, 'secured_area', $user->getRoles());
        $this->security->setToken($token);
        //roles
        $roles = $user->getRoles();
        foreach ($roles as &$role) {
            //check that each role was granted correctly
            $ok = ($this->security->isGranted($role));
        }
        if ($ok) {
            //serialize token and put it on session
            $this->session->set('_security_secured_area', serialize($token));
            $this->setCurrentUser();

            return $user;
        }

        return null;
    }

    /**
     * Set logged user
     */
    public function setCurrentUser()
    {
        if ($this->user instanceof User) {
            return;
        }

        if ($this->security->getToken() instanceof UsernamePasswordToken || $this->security->getToken() instanceof OAuthToken) {
            $this->user = $this->security->getToken()->getUser();
        } else if ($this->security->getToken()->getUser() instanceof User) {
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
