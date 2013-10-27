<?php
namespace NPS\ApiBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * DeviceApiService
 */
class DeviceApiService
{
    /**
     * @var $doctrine Doctrine
     */
    private $doctrine;

    /**
     * @var $secure SecureService
     */
    private $secure;


    /**
     * @param Registry     $doctrine Doctrine Registry
     * @param SecureService $secure  SecureService
     */
    public function __construct(Registry $doctrine, SecureService $secure)
    {
        $this->doctrine = $doctrine;
        $this->secure = $secure;
    }

    /**
     * Do login for Chrome Api
     * @param $appKey
     *
     * @return array
     */
    public function loginChromeApi($appKey)
    {
        $response = false;

        $user = $this->secure->getUserByDevice($appKey);
        if ($user instanceof User) {
            $response = true;
        }
        $responseData = array(
            'response' => $response
        );

        return $responseData;
    }

    /**
     * Login process for app api
     * @param string $appKey
     * @param string $username
     * @param string $password
     *
     * @return string
     */
    public function loginApi($appKey, $username, $password)
    {
        if (!$this->secure->checkLogged($appKey, $username, $password)) {
            return NotificationHelper::ERROR_LOGIN_DATA;
        }

        return NotificationHelper::OK;
    }

    /**
     * Send app key for Chrome api
     * @param string $username
     *
     * @return array
     */
    public function requestAppKey($username)
    {
        $response = false;
        $userRepo = $this->doctrine->getRepository('NPSCoreBundle:User');
        $user = $userRepo->findOneByUsername($username);
        if($user instanceof User){
            $deviceRepo = $this->doctrine->getRepository('NPSCoreBundle:Device');
            $extensionKey = substr(hash("sha1", uniqid(rand(), true)), 0, 16);

            //send email to user with new key
            $this->userNotify->sendChromeKey($user, $extensionKey);
            //save new key for extension
            $deviceRepo->createDevice($extensionKey, $user);

            $response = true;
        }
        $responseData = array(
            'response' => $response,
        );

        return $responseData;
    }

    /**
     * Sign up for app api
     * @param string $appKey
     * @param string $username
     * @param string $email
     * @param string $password
     *
     * @return string
     */
    public function signUpApi($appKey, $username, $email, $password)
    {
        $userRepo = $this->doctrine->getRepository('NPSCoreBundle:User');
        $checkUser = $userRepo->checkUserExists($username, $email);
        if ($checkUser) {
            return $checkUser;
        }

        $user = $userRepo->createUser($username, $email, $password);
        if (!$user instanceof User) {
            return NotificationHelper::ERROR_TRY_LATER;
        }

        $deviceRepo = $this->doctrine->getRepository('NPSCoreBundle:Device');
        $deviceRepo->createDevice($appKey, $user);
        $this->secure->saveTemporaryKey("device_".$appKey, $user->getId());

        return NotificationHelper::OK;
    }
}
