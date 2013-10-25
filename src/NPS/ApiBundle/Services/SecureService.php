<?php
namespace NPS\ApiBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\CoreBundle\Entity\Device;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Services\CacheService;

/**
 * SecureService
 */
class SecureService
{
    /**
     * @var $cache Redis
     */
    private $cache;

    /**
     * @var $doctrine Doctrine
     */
    private $doctrine;

    /**
     * @var $entityManager Entity Manager
     */
    private $entityManager;

    /**
     * @param Registry     $doctrine Doctrine Registry
     * @param CacheService $cache    CacheService
     */
    public function __construct(Registry $doctrine, CacheService $cache)
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
     * @param string $appKey
     * @param string $username
     * @param string $password
     *
     * @return bool | User
     */
    public function checkLogged($appKey, $username = null, $password = null)
    {
        $key = $this->cache->get("device_".$appKey);
        if ($key) {
            return true;
        }

        if ($this->checkLoggedCache($appKey, $username)) {
            return true;
        } elseif ($password) {
            return $this->checkLoggedDB($appKey, $username, $password);
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
     * @param $appKey
     * @param $username
     *
     * @return bool
     */
    private function checkLoggedCache($appKey, $username)
    {
        $deviceRepo = $this->entityManager->getRepository('NPSCoreBundle:Device');
        $device = $deviceRepo->findOneByAppKey($appKey);
        if ($device instanceof Device) {
            if ($username && $username != $device->getUser()->getUsername()) {
                return false;
            }
            $this->saveTemporaryKey($appKey, $device->getUserId());

            return true;
        }
    }

    /**
     * Check login data in data base
     * @param $appKey
     * @param $username
     * @param $password
     *
     * @return bool
     */
    private function checkLoggedDB($appKey, $username, $password)
    {
        $userRepo = $this->entityManager->getRepository('NPSCoreBundle:User');
        $checkUser = $userRepo->checkLogin($username, $password);

        if ($checkUser instanceof User) {
            $deviceRepo = $this->entityManager->getRepository('NPSCoreBundle:Device');
            $deviceRepo->createDevice($appKey, $checkUser);
            $this->saveTemporaryKey($appKey, $checkUser->getId());

            return true;
        }

        return false;
    }
}
