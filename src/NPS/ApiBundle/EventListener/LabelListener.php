<?php
namespace NPS\ApiBundle\EventListener;

use NPS\ApiBundle\Constant\SyncConstants;
use NPS\ApiBundle\Services\GcmService;
use NPS\CoreBundle\Event\LabelModifiedEvent;

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
     * @param LabelModifiedEvent $event
     */
    public function onLabelModified(LabelModifiedEvent $event)
    {
        $this->gcmService->requireToSync(SyncConstants::SYNC_LABELS, $event->getUserId());
    }
}
