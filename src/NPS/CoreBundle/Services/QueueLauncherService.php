<?php
namespace NPS\CoreBundle\Services;

use Mmoreram\RSQueueBundle\Services\Producer;

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
        $this->rsqueue->produce("crawler", $userId);
    }
}
