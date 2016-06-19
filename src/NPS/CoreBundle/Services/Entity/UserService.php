<?php

namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\CoreBundle\Constant\RedisConstants;
use NPS\CoreBundle\Repository\UserRepository;
use NPS\CoreBundle\Services\UserNotificationsService;
use Predis\Client;
use Symfony\Component\Form\Form;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Entity\Preference;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * UserService
 */
class UserService extends AbstractEntityService
{
    /** @var Client */
    protected $cache;

    /** @var UserNotificationsService */
    protected $userNotification;

    /** @var UserRepository */
    protected $userRepository;

    /**
     * @param Client $cache
     */
    public function setRedis(Client $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param UserNotificationsService $userNotification
     */
    public function setNotificationManager(UserNotificationsService $userNotification)
    {
        $this->userNotification = $userNotification;
    }

    /**
     * @inheritdoc
     */
    protected function setRepository()
    {
        $this->userRepository = $this->entityManager->getRepository(User::class);
    }

    /**
     * Save form of user preferences to data base
     *
     * @param Form $form
     */
    public function saveFormPreferences(Form $form)
    {
        $formObject = $form->getData();
        if ($form->isValid() && $formObject instanceof Preference) {
            $this->saveObject($formObject, true);
        } else {
            $this->notification->setFlashMessage(NotificationHelper::ALERT_FORM_DATA);
        }
    }

    /**
     * Save form of user to data base
     *
     * @param Form   $form
     * @param string $nseck secret key
     *
     * @return array
     */
    public function saveFormUser(Form $form, $nseck)
    {
        $check     = $this->checkFormUser($form);
        $user      = $check['user'];
        $existUser = $check['existUser'];
        if (!$check['errors'] && $existUser instanceof User) {
            $password = sha1($nseck . "_" . $user->getPassword());
            $this->newUserSets($existUser, $password);

            return array($existUser, $check['errors']);
        }

        if (!$check['errors'] && strlen($user->getEmail()) > 2) {
            $password = sha1($nseck . "_" . $user->getPassword());
            $this->newUserSets($user, $password);
        }

        return array($user, $check['errors']);
    }

    /**
     * Sets of new user and set email verification code
     *
     * @param User   $user
     * @param string $password
     */
    private function newUserSets(User $user, $password)
    {
        $user->setPassword($password);
        $user->setEnabled(false);
        $user->setRegistered(true);
        $this->saveObject($user, true);

        $this->setVerifyCode($user->getId());
    }

    /**
     * Set email activation code to cache
     *
     * @param int $userId
     */
    private function setVerifyCode($userId)
    {
        $activationCode = sha1(microtime());
        $this->cache->setex(RedisConstants::USER_ACTIVATION_CODE . '_' . $activationCode, 2592000, $userId); //7 days life
        $this->cache->setex(RedisConstants::USER_ACTIVATION_CODE . '_' . $userId, 2592000, $activationCode); //7 days life
    }

    /**
     * Get verified user and activate it
     *
     * @param $activationCode
     *
     * @return User|null
     */
    public function getUserByVerifyCode($activationCode)
    {
        $redisKey = RedisConstants::USER_ACTIVATION_CODE . '_' . $activationCode;
        $userId   = $this->cache->get($redisKey);
        if (!$userId) {
            return null;
        }

        $user = $this->userRepository->find($userId);
        if (!$user instanceof User) {
            return null;
        }

        $user->setEnabled(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Check user registration form
     *
     * @param Form $form Form
     *
     * @return array
     */
    protected function checkFormUser(Form $form)
    {
        $error     = false;
        $existUser = null;
        $user      = $form->getData();
        if (!$form->isValid() || !$user instanceof User) {
            $this->notification->setFlashMessage(NotificationHelper::ALERT_FORM_DATA);
            $errors = true;
        }

        if (empty($error)) {
            list($error, $existUser) = $this->userRepository->checkUserExists($user->getEmail());
        }

        $response = array(
            'errors'    => $error,
            'user'      => $user,
            'existUser' => $existUser
        );

        return $response;
    }

    /**
     * Set preference for new user
     *
     * @param User $user
     */
    public function setPreferenceNewUser(User $user)
    {
        $laterRepo = $this->doctrine->getRepository('NPSCoreBundle:Later');
        $readLater = $laterRepo->createLabel($user, 'Read later', true);
        $laterRepo->createLabel($user, 'Watch later', true);

        $preference = new Preference();
        $preference->setReadLater($readLater);
        $this->entityManager->persist($preference);

        $user->setPreference($preference);
        $this->entityManager->flush();
    }

    /**
     * Send verification email
     *
     * @param User $user
     */
    public function sendVerificationEmail(User $user)
    {
        $redisKey       = RedisConstants::USER_ACTIVATION_CODE . '_' . $user->getId();
        $activationCode = $this->cache->get($redisKey);
        $this->userNotification->sendEmailVerification($user->getEmail(), $activationCode);
    }

    /**
     * Send an email with link to create new password
     *
     * @param $email
     */
    public function requestRecoverPassword($email)
    {
        $user = $this->userRepository->findOneByEmail($email);
        if (!$user instanceof User) {
            return;
        }

        $recoveryCode = sha1(microtime());
        $this->cache->setex(RedisConstants::USER_PASSWORD_RECOVERY . '_' . $recoveryCode, 2592000, $user->getId()); //7 days life
        $this->userNotification->sendPasswordRecovery($user->getEmail(), $recoveryCode);
    }

    /**
     * Get user from recovery password code
     *
     * @param string $nseck        system secret code
     * @param string $recoveryCode recovery password code
     * @param string $password     new password
     *
     * @return User|null
     */
    public function newRecoveryPassword($nseck, $recoveryCode, $password)
    {
        $userId = $this->cache->get(RedisConstants::USER_PASSWORD_RECOVERY . '_' . $recoveryCode);
        $user   = $this->userRepository->find($userId);
        if (!$user instanceof User) {
            return null;
        }

        $password = sha1($nseck . "_" . $password);
        $user->setPassword($password);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
