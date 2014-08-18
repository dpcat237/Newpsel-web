<?php
namespace NPS\ApiBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

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
     * @var EncoderFactory
     */
    private $encoderFactory;

    /**
     * @var string
     */
    private $salt;

    /**
     * @var $secure SecureService
     */
    private $secure;


    /**
     * @param Registry     $doctrine Doctrine Registry
     * @param SecureService $secure  SecureService
     * @param EncoderFactory $encoderFactory EncoderFactory
     * @param string         $salt           salt key
     */
    public function __construct(Registry $doctrine, SecureService $secure, EncoderFactory $encoderFactory, $salt)
    {
        $this->doctrine = $doctrine;
        $this->encoderFactory = $encoderFactory;
        $this->salt = $salt;
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
     *
     * @param string $appKey
     * @param string $email
     * @param string $password
     *
     * @return string
     */
    public function loginApi($appKey, $email, $password)
    {
        if (!$this->secure->checkLogged($appKey, $email, $password)) {
            return NotificationHelper::ERROR_LOGIN_DATA;
        }

        return NotificationHelper::OK;
    }

    /**
     * Send app key for Chrome api
     *
     * @param string $email
     *
     * @return array
     */
    public function requestAppKey($email)
    {
        $response = false;
        $userRepo = $this->doctrine->getRepository('NPSCoreBundle:User');
        $user = $userRepo->findOneByEmail($email);
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
     *
     * @param string $appKey
     * @param string $email
     * @param string $password
     *
     * @return string
     */
    public function signUpApi($appKey, $email, $password = '')
    {
        $userRepo = $this->doctrine->getRepository('NPSCoreBundle:User');
        $user = $userRepo->findOneByEmail($email);

        if ($user instanceof User && $password) {
            return NotificationHelper::ERROR_EMAIL_EXISTS;
        }

        if (!$password && !$user instanceof User) {
            $encoder = $this->encoderFactory->getEncoder(new User());
            $password = $encoder->encodePassword(md5(uniqid()), $this->salt);
            $password = substr($password, 0, 16);
        }

        if (!$user instanceof User) {
            $user = $userRepo->createUser($email, $password);
        }

        $deviceRepo = $this->doctrine->getRepository('NPSCoreBundle:Device');
        $deviceRepo->createDevice($appKey, $user);
        $this->secure->saveTemporaryKey("device_".$appKey, $user->getId());

        return NotificationHelper::OK;
    }

    /**
     * Update Google Cloud Messaging ID of Android device
     *
     * @param string $appKey
     * @param string $gcmId
     *
     * @return array
     */
    public function updateGcmId($appKey, $gcmId)
    {
        $error = false;
        $user = $this->secure->getUserByDevice($appKey);
        if (!$user instanceof User) {
            $error = NotificationHelper::ERROR_NO_LOGGED;
        }

        if (!$error) {
            $deviceRepo = $this->doctrine->getRepository('NPSCoreBundle:Device');
            $deviceRepo->removeLogOutDevices($appKey, $gcmId);
            $deviceRepo->updateGcmId($appKey, $gcmId);
        }

        $responseData = array(
            'error' => $error,
        );

        return $responseData;
    }
}
