<?php
namespace NPS\ApiBundle\Services;

use Endroid\Gcm\Gcm;
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
     * @var Gcm
     */
    private $gcm;

    /**
     * @param Gcm              $gcm        Google Central Messaging Service
     * @param DeviceRepository $deviceRepo DeviceRepository
     */
    public function __construct(Gcm $gcm, DeviceRepository $deviceRepo)
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
