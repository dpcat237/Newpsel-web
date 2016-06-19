<?php

namespace NPS\ApiBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Entity\Device;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Repository\DeviceRepository;
use NPS\CoreBundle\Services\UserNotificationsService;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;

/**
 * Class DeviceApiService
 *
 * @package NPS\ApiBundle\Services\Entity
 */
class DeviceApiService
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var DeviceRepository */
    protected $deviceRepository;

    /** @var UserNotificationsService */
    protected $userNotification;

    /** @var $secure SecureService */
    protected $secure;

    /** @var UserApiService */
    protected $userService;

    /**
     * DeviceApiService constructor.
     *
     * @param EntityManager            $entityManager
     * @param SecureService            $secure
     * @param UserNotificationsService $userNotification
     * @param UserApiService           $userService
     */
    public function __construct(
        EntityManager $entityManager,
        SecureService $secure,
        UserNotificationsService $userNotification,
        UserApiService $userService
    )
    {
        $this->entityManager    = $entityManager;
        $this->secure           = $secure;
        $this->userNotification = $userNotification;
        $this->userService      = $userService;
        $this->deviceRepository = $entityManager->getRepository(Device::class);
    }

    /**
     * Do login for Chrome Api
     *
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
        $user     = $this->userService->findByEmail($email);
        if ($user instanceof User) {
            $extensionKey = substr(hash("sha1", uniqid(rand(), true)), 0, 16);

            //send email to user with new key
            $this->userNotification->sendChromeKey($user->getEmail(), $extensionKey);
            //save new key for extension
            $this->deviceRepository->createDevice($extensionKey, $user);

            $response = true;
        }
        $responseData = array(
            'response' => $response,
        );

        return $responseData;
    }

    /**
     * Register user device
     *
     * @param string $appKey
     * @param string $email
     * @param string $password
     */
    public function registerUserDevice($appKey, $email, $password = '')
    {
        $user = $this->userService->registerUser($email, $password);
        $this->deviceRepository->createDevice($appKey, $user);
        $this->secure->saveTemporaryKey("device_" . $appKey, $user->getId());
    }

    /**
     * Create preview user and new device
     *
     * @param string $appKey
     */
    public function registerPreviewDevice($appKey)
    {
        $user = $this->userService->createPreviewUser();
        $this->deviceRepository->createDevice($appKey, $user);
        $this->secure->saveTemporaryKey("device_" . $appKey, $user->getId());
    }

    /**
     * Add data to preview user
     *
     * @param string $appKey
     * @param string $email
     * @param string $password
     */
    public function addPreviewUserData($appKey, $email, $password)
    {
        $user = $this->secure->getUserByDevice($appKey);
        $this->userService->addDataPreviewUser($user, $email, $password);
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
        $user  = $this->secure->getUserByDevice($appKey);
        if (!$user instanceof User) {
            $error = NotificationHelper::ERROR_NO_LOGGED;
        }

        if (!$error) {
            $this->deviceRepository->removeLogOutDevices($appKey, $gcmId);
            $this->deviceRepository->updateGcmId($appKey, $gcmId);
        }

        $responseData = array(
            'error' => $error,
        );

        return $responseData;
    }
}
