<?php

namespace NPS\ApiBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use NPS\CoreBundle\Entity\Device;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Repository\DeviceRepository;
use NPS\CoreBundle\Repository\UserRepository;
use Predis\Client;

/**
 * Class SecureService
 *
 * @package NPS\ApiBundle\Services
 */
class SecureService
{
    /** @var Client */
    protected $cache;

    /** @var DeviceRepository */
    protected $deviceRepository;

    /** @var UserRepository */
    protected $userRepository;

    /**
     * SecureService constructor.
     *
     * @param EntityManager $entityManager
     * @param Client        $cache
     */
    public function __construct(EntityManager $entityManager, Client $cache)
    {
        $this->cache            = $cache;
        $this->deviceRepository = $entityManager->getRepository(Device::class);
        $this->userRepository   = $entityManager->getRepository(User::class);
    }

    /**
     * Get device user
     *
     * @param string $deviceId device appKey
     *
     * @return mixed
     */
    public function getUserByDevice($deviceId)
    {
        if ($this->checkLogged($deviceId)) {
            $userId = $this->cache->get("device_" . $deviceId);

            return $this->userRepository->find($userId);
        }

        return null;
    }

    /**
     * Check if device is logged
     *
     * @param string $deviceId device key
     * @param string $email    email
     * @param string $password password
     *
     * @return bool | User
     */
    public function checkLogged($deviceId, $email = null, $password = null)
    {
        $key = $this->cache->get("device_" . $deviceId);
        if ($key) {
            return true;
        }

        if ($this->checkLoggedCache($deviceId, $email)) {
            return true;
        } elseif ($password) {
            return $this->checkLoggedDB($deviceId, $email, $password);
        }

        return false;
    }

    /**
     * Set device to cache
     *
     * @param $deviceId
     * @param $userId
     */
    public function saveTemporaryKey($deviceId, $userId)
    {
        $this->cache->set("device_" . $deviceId, $userId);
    }

    /**
     * Check if logged device, in cache
     *
     * @param $deviceId
     * @param $email
     *
     * @return bool
     */
    private function checkLoggedCache($deviceId, $email = null)
    {
        $device = $this->deviceRepository->findOneByAppKey($deviceId);
        if ($device instanceof Device) {
            if ($email && $email != $device->getUser()->getEmail()) {
                return false;
            }
            $this->saveTemporaryKey($deviceId, $device->getUserId());

            return true;
        }
    }

    /**
     * Check login data in data base
     *
     * @param string $deviceId device key
     * @param string $email    email
     * @param string $password password
     *
     * @return bool
     */
    private function checkLoggedDB($deviceId, $email, $password)
    {
        $user = $this->userRepository->findOneByEmail($email);
        if (!$user instanceof User) {
            return false;
        }

        if ($user->getPassword() != $password) {
            return false;
        }

        $this->deviceRepository->createDevice($deviceId, $user);
        $this->saveTemporaryKey($deviceId, $user->getId());

        return true;
    }
}
