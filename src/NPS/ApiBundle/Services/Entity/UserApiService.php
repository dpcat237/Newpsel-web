<?php

namespace NPS\ApiBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use NPS\ApiBundle\Exception\UserExistsException;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Event\UserSignUpEvent;
use NPS\CoreBundle\NPSCoreEvents;
use NPS\CoreBundle\Repository\UserRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

/**
 * Class UserApiService
 *
 * @package NPS\ApiBundle\Services\Entity
 */
class UserApiService
{
    /** @var EntityManager */
    private $entityManager;

    /** @var EncoderFactory */
    private $encoderFactory;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var UserRepository */
    protected $userRepository;

    /** @var string */
    private $salt;

    /**
     * UserApiService constructor.
     *
     * @param EntityManager            $entityManager
     * @param EncoderFactory           $encoderFactory
     * @param EventDispatcherInterface $eventDispatcher
     * @param string                   $salt
     */
    public function __construct(
        EntityManager $entityManager,
        EncoderFactory $encoderFactory,
        EventDispatcherInterface $eventDispatcher,
        $salt
    )
    {
        $this->entityManager   = $entityManager;
        $this->encoderFactory  = $encoderFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->salt            = $salt;
        $this->userRepository  = $entityManager->getRepository(User::class);
    }

    /**
     * Find one User by email
     *
     * @param $email
     *
     * @return User
     */
    public function findByEmail($email)
    {
        return $this->userRepository->findOneByEmail($email);
    }

    /**
     * Get existed or register new user for new device
     *
     * @param string $email
     * @param string $password
     *
     * @return User
     */
    public function registerUser($email, $password = '')
    {
        list($error, $user) = $this->userRepository->checkUserExists($email);
        if ($user instanceof User && $error && $password) {
            throw new UserExistsException();
        }

        if (!$user instanceof User) {
            $password = ($password) ? $this->encodePassword($password) : $this->generatePassword();
            $user     = $this->userRepository->createUser($email, $password);

            $userSignUpEvent = new UserSignUpEvent($user);
            $this->eventDispatcher->dispatch(NPSCoreEvents::USER_SIGN_UP, $userSignUpEvent);
        }

        return $user;
    }

    /**
     * Create preview user to register device without user data
     *
     * @return User
     */
    public function createPreviewUser()
    {
        $user            = $this->userRepository->createUser($this->generateUniqueEmail(), $this->generatePassword(), true);
        $userSignUpEvent = new UserSignUpEvent($user);
        $this->eventDispatcher->dispatch(NPSCoreEvents::USER_SIGN_UP, $userSignUpEvent);

        return $user;
    }

    /**
     * Generate preview email
     *
     * @return string
     */
    protected function generateUniqueEmail()
    {
        return substr(md5(uniqid()), 0, 16) . '@preview.com';
    }

    /**
     * Generate new password
     *
     * @return string
     */
    protected function generatePassword()
    {
        $encoder     = $this->encoderFactory->getEncoder(new User());
        $newPassword = $encoder->encodePassword(md5(uniqid()), $this->salt);

        return substr($newPassword, 0, 16);
    }

    /**
     * Encode password
     *
     * @param string    $password
     * @param null|User $user
     *
     * @return string
     */
    protected function encodePassword($password, $user = null)
    {
        $user    = ($user instanceof User) ?: new User();
        $encoder = $this->encoderFactory->getEncoder($user);

        return $encoder->encodePassword($password, $this->salt);
    }

    /**
     * Add data for preview user to make it normal
     *
     * @param User   $user
     * @param string $email
     * @param string $password
     */
    public function addDataPreviewUser(User $user, $email, $password = '')
    {
        if (!$user->isPreview()) {
            throw new UserExistsException();
        }

        $password = ($password) ? $this->encodePassword($password, $user) : $this->generatePassword();
        $user->setEmail($email);
        $user->setPassword($password);
        $user->setPreview(false);
        $this->entityManager->flush($user);
    }
}
