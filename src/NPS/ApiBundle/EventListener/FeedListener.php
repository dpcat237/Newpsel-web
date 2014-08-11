<?php
namespace NPS\ApiBundle\EventListener;

use NPS\ApiBundle\Constant\SyncConstants;
use NPS\ApiBundle\Services\GcmService;
use NPS\CoreBundle\Event\FeedModifiedEvent;

/**
 * Class FeedListener
 *
 * @package NPS\ApiBundle\EventListener
 */
class FeedListener
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
     * @param FeedModifiedEvent $event
     */
    public function onFeedModified(FeedModifiedEvent $event)
    {
        $this->gcmService->requireToSync(SyncConstants::SYNC_FEEDS, $event->getUserId());
    }
}

