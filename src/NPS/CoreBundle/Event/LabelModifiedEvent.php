<?php
namespace NPS\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event fired when some label was: created, changed or deleted
 *
 * This event send an email to customer
 */
class LabelModifiedEvent extends Event
{
    /**
     * @var int
     */
    private $userId;

    /**
     * construct method
     *
     * @param int $userId
     */
    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Get user id
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }
}
