<?php
namespace NPS\ApiBundle\Services;

use Endroid\Gcm\Client;
use NPS\ApiBundle\Constant\SyncConstants;
use NPS\CoreBundle\Repository\DeviceRepository;

/**
 * Class Google Central Messaging Service
 *
 * @package NPS\ApiBundle\Services
 */
class GcmService
{
    /**
     * @var DeviceRepository
     */
    private $deviceRepo;

    /**
     * @var Client
     */
    private $gcm;

    /**
     * GcmService constructor.
     *
     * @param Client           $gcm
     * @param DeviceRepository $deviceRepo
     */
    public function __construct(Client $gcm, DeviceRepository $deviceRepo)
    {
        $this->deviceRepo = $deviceRepo;
        $this->gcm = $gcm;
    }

    /**
     * Set message to GCM about requirement of sync
     *
     * @param string $command
     * @param int $userId
     *
     * @return bool
     */
    public function requireToSync($command, $userId)
    {
        $devices = $this->deviceRepo->findByUser($userId);
        if (!count($devices)) {
            return false;
        }

        $registrationIds = array();
        foreach ($devices as $device) {
            if (!$device->getGcmId()) {
                continue;
            }
            $registrationIds[] = $device->getGcmId();
        }
        if (!count($registrationIds)) {
            return false;
        }

        $data = array(
            'title' => SyncConstants::TITLE,
            'message' => $command,
        );

        return $this->gcm->send($data, $registrationIds);
    }
}
