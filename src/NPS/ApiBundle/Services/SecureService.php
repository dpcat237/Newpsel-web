<?php
namespace NPS\ApiBundle\Services;

use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Entity\Device;

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
     * @param Doctrine     $doctrine
     * @param CacheService $cache
     */
    public function __construct($doctrine, $cache)
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
     *
     * @return bool | User
     */
    public function checkLogged($appKey, $username = null)
    {
        $key = $this->cache->get("device_".$appKey);
        if ($key) {
            return true;
        }

        $deviceRepo = $this->entityManager->getRepository('NPSCoreBundle:Device');
        $device = $deviceRepo->findOneByAppKey($appKey);
        if ($device instanceof Device) {
            if ($username && $username != $device->getUser()->getUsername()) {
                return false;
            }
            $this->cache->set("device_".$appKey, $device->getUserId());

            return true;
        }

        return false;
    }
}
