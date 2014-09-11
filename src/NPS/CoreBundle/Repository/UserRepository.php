<?php

namespace NPS\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Entity\User;

/**
 * Class UserRepository
 *
 * @package NPS\CoreBundle\Repository
 */
class UserRepository extends EntityRepository
{
    /**
     * Check user data for login
     *
     * @param string $email
     * @param string $password
     *
     * @return int|User
     */
    public function checkLogin($email, $password){
        $user = $this->findOneByEmail($email);
        if (!$user instanceof User) {
            return NotificationHelper::ERROR_LOGIN_DATA;
        }

        $appKey = sha1("checkPwd_".$user->getPassword());

        return ($password == $appKey)? $user : NotificationHelper::ERROR_LOGIN_DATA;
    }

    /**
     * Check if email exists
     *
     * @param string $email
     *
     * @return bool|int
     */
    public function checkUserExists($email)
    {
        $user = $this->findOneByEmail($email);
        if (!$user instanceof User) {
            return array(false, null);
        }
        $error = (($user->isRegistered()) ? NotificationHelper::ERROR_EMAIL_EXISTS : false);

        return array($error, $user);
    }

    /**
     * Create User
     *
     * @param string $email
     * @param string $password
     *
     * @return User
     */
    public function createUser($email, $password)
    {
        $entityManager = $this->getEntityManager();
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($password);
        $user->setRegistered(true);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    /**
     * Subscribe email to newsletter
     * @param $email
     */
    public function subscribeToNewsletter($email)
    {
        $entityManager = $this->getEntityManager();
        if (!$this->isInNewsletter($email)) {
            $user = $this->findOneByEmail($email);
            if (!$user instanceof User) {
                $user = new User();
                $user->setEmail($email);
            }
            $user->setSubscribed(true);
            $entityManager->persist($user);
            $entityManager->flush();
        }
    }

    /**
     * Check if user are un newsletter list
     *
     * @param $email
     *
     * @return bool
     */
    public function isInNewsletter($email)
    {
        $user = $this->findOneByEmail($email);
        if ($user instanceof User) {
            if ($user->isSubscribed()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find user by email as username
     *
     * @param string $username
     *
     * @return null
     */
    public function findOneByUsername($username)
    {
        $user = $this->findOneByEmail($username);
        if (!$user instanceof User) {
            return null;
        }
    }
}
