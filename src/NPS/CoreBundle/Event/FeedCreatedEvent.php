<?php
namespace NPS\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use NPS\CoreBundle\Entity\Feed;

/**
 * Event fired when feed is created
 *
 * This event send an email to customer
 */
class FeedCreatedEvent extends Event
{
    /**
     * @var Feed
     */
    private $feed;

    /**
     * construct method
     *
     * @param Feed $feed the feed
     */
    public function __construct(Feed $feed)
    {
        $this->feed = $feed;
    }

    /**
     * Get Feed
     *
     * @return Feed
     */
    public function getFeed()
    {
        return $this->feed;
    }
}
