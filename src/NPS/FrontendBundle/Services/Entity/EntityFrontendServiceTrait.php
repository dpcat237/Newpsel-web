<?php

namespace NPS\FrontendBundle\Services\Entity;

use Exception;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Services\NotificationManager;

/**
 * Class EntityFrontendServiceTrait
 *
 * @package NPS\FrontendBundle\Services\Entity
 */
trait EntityFrontendServiceTrait
{
    /** @var NotificationManager */
    protected $notification;

    /**
     * Save object to data base
     *
     * @param $callTo
     * @param $object
     */
    protected function saveNotification($callTo, $object)
    {
        try {
            $callTo->saveObject($object);
            $this->notification->setFlashMessage(NotificationHelper::SAVED_OK);
        } catch (Exception $e) {
            $this->notification->setLog(__METHOD__ . ' ' . $e->getMessage(), 'err');
            $this->notification->setFlashMessage(NotificationHelper::ERROR_TRY_AGAIN);
        }
    }
}
