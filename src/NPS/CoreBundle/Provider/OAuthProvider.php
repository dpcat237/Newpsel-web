<?php
namespace NPS\CoreBundle\Provider;

use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthUserProvider;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use NPS\CoreBundle\Event\UserSignUpEvent;
use NPS\CoreBundle\NPSCoreEvents;
use NPS\CoreBundle\Repository\UserRepository;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use NPS\CoreBundle\Entity\User;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;

/**
 * Class OAuthProvider
 *
 * @package NPS\CoreBundle\Provider
 */
class OAuthProvider extends OAuthUserProvider
{
    /**
     * @var EncoderFactory
     */
    private $encoderFactory;

    /**
     * @var ContainerAwareEventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var string
     */
    private $salt;

    /**
     * @var UserRepository
     */
    private $userRepo;


    /**
     * Constructor
     *
     * @param UserRepository $userRepo       UserRepository
     * @param EncoderFactory $encoderFactory EncoderFactory
     * @param string         $salt           salt key
     */
    public function __construct(UserRepository $userRepo, ContainerAwareEventDispatcher $eventDispatcher, EncoderFactory $encoderFactory, $salt)
    {
        $this->encoderFactory = $encoderFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->salt = $salt;
        $this->userRepo = $userRepo;
    }

    /**
     * Load user by email as username
     *
     * @param string $username
     *
     * @return User
     */
    public function loadUserByUsername($username)
    {
        $user = $this->userRepo->findOneByEmail($username);
        if ($user instanceof User) {
            return $user;
        }

        return new User();
    }

    /**
     * Load user from UserResponseInterface and do login or sign up
     *
     * @param UserResponseInterface $response
     *
     * @return User
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        $email = $response->getEmail();
        $user = $this->userRepo->findOneByEmail($email);
        if ($user instanceof User) {
            return $user;
        }

        $encoder = $this->encoderFactory->getEncoder(new User());
        $password = $encoder->encodePassword(md5(uniqid()), $this->salt);
        $password = substr($password, 0, 16);

        $user =$this->userRepo->createUser($email, $password);
        $userSignUpEvent = new UserSignUpEvent($user);
        $this->eventDispatcher->dispatch(NPSCoreEvents::USER_SIGN_UP, $userSignUpEvent);

        return $user;
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return $class === 'NPS\\CoreBundle\\Entity\\User';
    }
} 