<?php
namespace NPS\ApiBundle\EventListener;

use NPS\ApiBundle\Constant\SyncConstants;
use NPS\ApiBundle\Services\GcmService;
use NPS\CoreBundle\Event\LabelsModifiedEvent;

/**
 * Class LabelListener
 *
 * @package NPS\ApiBundle\EventListener
 */
class LabelListener
{
    /**
     * @var GcmService
     */
    private $gcmService;

    /**
     * @param GcmService $gcmService GcmService
     */
    public function __construct(GcmService $gcmService)
    {
        $this->gcmService = $gcmService;
    }

    /**
     * Make necessary processes after any modification with labels
     *
     * @param LabelsModifiedEvent $event
     */
    public function onLabelModified(LabelsModifiedEvent $event)
    {
        $this->gcmService->requireToSync(SyncConstants::SYNC_LABELS, $event->getUserId());
    }
}

