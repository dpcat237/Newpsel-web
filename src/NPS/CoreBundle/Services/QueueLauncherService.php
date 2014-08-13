<?php
namespace NPS\CoreBundle\Services;

use Mmoreram\RSQueueBundle\Services\Producer;
use NPS\CoreBundle\Constant\QueueConstants;

/**
 * QueueLauncherService
 */
class QueueLauncherService
{
    /**
     * @var Producer
     */
    private $rsqueue;


    /**
     * @param Producer $rsqueue  RQ queue producer
     */
    public function __construct(Producer $rsqueue)
    {
        $this->rsqueue = $rsqueue;
    }

    /**
     * Add crawling process to queue
     *
     * @param string $userId
     */
    public function executeCrawling($userId = null)
    {
        $this->rsqueue->produce(QueueConstants::ITEMS_CRAWLER, $userId);
    }

    /**
     * Add import later items process to queue
     *
     * @param string $redisKey
     */
    public function executeImportItems($redisKey)
    {
        $this->rsqueue->produce(QueueConstants::IMPORT_LATER_ITEMS, $redisKey);
    }
}
