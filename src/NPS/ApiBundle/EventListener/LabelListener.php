<?php
namespace NPS\ApiBundle\EventListener;

use NPS\ApiBundle\Constant\SyncConstants;
use NPS\ApiBundle\Services\Entity\LabelApiService;
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
     * @var LabelApiService
     */
    private $labelApi;


    /**
     * @param GcmService      $gcmService GcmService
     * @param LabelApiService $labelApi   LabelApiService
     */
    public function __construct(GcmService $gcmService, LabelApiService $labelApi)
    {
        $this->gcmService = $gcmService;
        $this->labelApi = $labelApi;
    }

    /**
     * Make necessary processes after any modification with labels
     *
     * @param LabelModifiedEvent $event
     */
    public function onLabelModified(LabelModifiedEvent $event)
    {
        $this->gcmService->requireToSync(SyncConstants::SYNC_LABELS, $event->getUserId());
        $this->labelApi->updateLabelsTree($event->getUserId());
    }
}

