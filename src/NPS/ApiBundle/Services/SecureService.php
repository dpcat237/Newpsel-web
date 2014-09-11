<?php
namespace NPS\ApiBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\CoreBundle\Entity\Device;
use NPS\CoreBundle\Entity\User;
use Predis\Client;

/**
 * SecureService
 */
class SecureService
{
    /**
     * @var Client
     */
    private $cache;

    /**
     * @var Doctrine
     */
    private $doctrine;

    /**
     * @var Entity Manager
     */
    private $entityManager;

    /**
     * @param Registry $doctrine Doctrine Registry
     * @param Client   $cache    Client
     */
    public function __construct(Registry $doctrine, Client $cache)
    {
        $this->cache = $cache;
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
    }

    /**
     * Get device user
     * @param string $appKey device appKey
     *
     * @return mixed
     */
    public function getUserByDevice($appKey)
    {
        if($this->checkLogged($appKey)) {
            $userId = $this->cache->get("device_".$appKey);
            $userRepo = $this->entityManager->getRepository('NPSCoreBundle:User');

            return $userRepo->find($userId);
        }

        return null;
    }

    /**
     * Check if device is logged
     *
     * @param string $appKey   device key
     * @param string $email    email
     * @param string $password password
     *
     * @return bool | User
     */
    public function checkLogged($appKey, $email = null, $password = null)
    {
        $key = $this->cache->get("device_".$appKey);
        if ($key) {
            return true;
        }

        if ($this->checkLoggedCache($appKey, $email)) {
            return true;
        } elseif ($password) {
            return $this->checkLoggedDB($appKey, $email, $password);
        }

        return false;
    }

    /**
     * Set device to cache
     * @param $appKey
     * @param $userId
     */
    public function saveTemporaryKey($appKey, $userId)
    {
        $this->cache->set("device_".$appKey, $userId);
    }

    /**
     * Check if logged device, in cache
     *
     * @param $appKey
     * @param $email
     *
     * @return bool
     */
    private function checkLoggedCache($appKey, $email = null)
    {
        $deviceRepo = $this->entityManager->getRepository('NPSCoreBundle:Device');
        $device = $deviceRepo->findOneByAppKey($appKey);
        if ($device instanceof Device) {
            if ($email && $email != $device->getUser()->getEmail()) {
                return false;
            }
            $this->saveTemporaryKey($appKey, $device->getUserId());

            return true;
        }
    }

    /**
     * Check login data in data base
     *
     * @param string $appKey   device key
     * @param string $email    email
     * @param string $password password
     *
     * @return bool
     */
    private function checkLoggedDB($appKey, $email, $password)
    {
        $user = $this->entityManager->getRepository('NPSCoreBundle:User')->findOneByEmail($email);
        if (!$user instanceof User) {
            return false;
        }

        if ($user->getPassword() != $password) {
            return false;
        }

        $deviceRepo = $this->entityManager->getRepository('NPSCoreBundle:Device');
        $deviceRepo->createDevice($appKey, $user);
        $this->saveTemporaryKey($appKey, $user->getId());

        return true;
    }
}
