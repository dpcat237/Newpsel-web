<?php

namespace NPS\CoreBundle\Services;

use NPS\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * UserWrapper
 */
class UserWrapper
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;
    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var Session */
    private $session;
    /** @var User */
    private $user;

    /**
     * UserWrapper constructor.
     *
     * @param TokenStorageInterface         $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param Session                       $session
     */
    public function __construct(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker, Session $session)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->session              = $session;
        $this->tokenStorage         = $tokenStorage;
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
        $this->tokenStorage->setToken($token);
        //roles
        $roles = $user->getRoles();
        foreach ($roles as &$role) {
            //check that each role was granted correctly
            $ok = ($this->authorizationChecker->isGranted($role));
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

        if ($this->tokenStorage->getToken() instanceof UsernamePasswordToken || $this->tokenStorage->getToken() instanceof OAuthToken) {
            $this->user = $this->tokenStorage->getToken()->getUser();
        } else {
            if (is_object($this->tokenStorage->getToken()) && $this->tokenStorage->getToken()->getUser() instanceof User) {
                $this->user = $this->tokenStorage->getToken()->getUser();
            }
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
