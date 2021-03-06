<?php

namespace NPS\CoreBundle\Services;

use Doctrine\ORM\EntityManager;
use Exception;
use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\CoreBundle\Event\FeedCreatedEvent;
use NPS\CoreBundle\NPSCoreEvents;
use NPS\FrontendBundle\Services\Entity\FeedFrontendService;
use Predis\Client;
use SimplePie;
use SimplePie_Item;
use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Entity\Item;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Services\Entity\FeedHistoryService;
use NPS\CoreBundle\Services\Entity\ItemService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * DownloadFeedsService
 */
class DownloadFeedsService
{
    CONST REDIS_KEY = 'feed';

    /** @var int */
    private $countNewItems;
    /** @var EntityManager */
    private $entityManager;

    /** @var string Last process error */
    private $error = null;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var FeedFrontendService */
    private $feedS;

    /** @var FeedHistoryService */
    private $feedHistoryS;

    /** @var ItemService */
    private $itemS;

    /** @var SimplePie RSS */
    private $rss;

    /** @var Client Redis */
    private $redis;


    /**
     * DownloadFeedsService constructor.
     *
     * @param EntityManager            $entityManager
     * @param SimplePie                $rss
     * @param Client                   $redis
     * @param FeedFrontendService      $feed
     * @param ItemService              $itemS
     * @param FeedHistoryService       $feedHistoryS
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        EntityManager $entityManager,
        SimplePie $rss,
        Client $redis,
        FeedFrontendService $feed,
        ItemService $itemS,
        FeedHistoryService $feedHistoryS,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->entityManager   = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->rss             = $rss;
        $this->redis           = $redis;
        $this->feedS           = $feed;
        $this->itemS           = $itemS;
        $this->feedHistoryS    = $feedHistoryS;
    }

    /**
     * Create feed if necessary and subscribe to it
     *
     * @param string $url
     * @param User   $user
     *
     * @throws Exception it's necessary set $rss
     * @return array
     */
    public function addFeed($url, User $user)
    {
        $url = $this->checkUrl($url);
        if (!$url) {
            $result[] = null;
            $result[] = $this->error;

            return $result;
        }

        $feed = $this->feedS->checkFeedByUrl($url);
        if ($feed instanceof Feed) {
            $this->feedS->subscribeUser($user, $feed);
            $this->itemS->addLastItemsNewUser($user, $feed);
        } else {
            $feed = $this->createFeed($url, $user);
            $this->eventDispatcher->dispatch(NPSCoreEvents::FEED_CREATED, new FeedCreatedEvent($feed));
        }

        return array($feed, $this->error);
    }

    /**
     * Add news items of feed
     *
     * @param Feed  $feed     Feed
     * @param array $newItems array of new items to sync
     */
    private function addNewItems(Feed $feed, $newItems)
    {
        $this->countNewItems = 0;
        foreach ($newItems as $newItem) {
            $this->addUpdateItem($newItem, $feed);
        }

        //update last sync data
        $feed->setDateSync();
        $this->entityManager->persist($feed);
        $this->entityManager->flush();

        //update feed sync history
        $this->feedHistoryS->updateFeedSyncHistory($feed, $this->countNewItems);
    }

    /**
     * Add or update existing item
     *
     * @param SimplePie_Item $itemData
     * @param Feed           $feed
     */
    protected function addUpdateItem(SimplePie_Item $itemData, Feed $feed)
    {
        $item = $this->itemS->checkItemWasUpdated($itemData->get_link(), $itemData->get_title());
        if ($item instanceof Item) {
            $this->itemS->updateItemContent($item, $itemData);
        } else {
            $this->itemS->addNewItem($itemData, $feed);
            $this->countNewItems++;
        }
    }

    /**
     * Begin update process of feed's data
     *
     * @param Feed $feed
     *
     * @return null|string
     */
    protected function beginUpdateFeedData(Feed $feed)
    {
        $error = null;
        $this->initRss($feed->getUrl());
        $rssError = $this->rss->error();

        if ($rssError) {
            return $rssError;
        }

        $newItems = $this->getItemToSync($feed);
        if (count($newItems)) {
            $this->addNewItems($feed, $newItems);
        }

        return $error;
    }

    /**
     * Check if feed content is changed
     *
     * @param Feed $feed Feed
     *
     * @return bool
     */
    protected function checkDataChanged(Feed $feed)
    {
        try {
            $currentHash = sha1_file($feed->getUrl());
        } catch (Exception $e) {
            $this->feedHistoryS->dataIsSame($feed);

            return false;
        }


        if ($currentHash == $this->redis->hget(self::REDIS_KEY, "feed_" . $feed->getId() . "_data_hash")) {
            $this->feedHistoryS->dataIsSame($feed);

            return false;
        }
        $this->redis->hset(self::REDIS_KEY, "feed_" . $feed->getId() . "_data_hash", $currentHash);

        return true;
    }

    /**
     * Create new feed, subscribe an user and update feed's data
     *
     * @param      $url
     * @param User $user
     *
     * @return Feed|null
     */
    protected function createFeed($url, User $user)
    {
        $feed = $this->createFeedProcess($url);
        if (empty($this->error)) {
            $this->feedS->subscribeUser($user, $feed);
            $this->updateFeedData($feed);
            $this->entityManager->flush();

            return $feed;
        }

        return null;
    }

    /**
     * Fix and check url
     *
     * @param $url
     *
     * @return null|string
     */
    protected function checkUrl($url)
    {
        $url = $this->fixUrl($url);
        $url = $this->removeUnnecessaryCharactersUrl($url);

        if (!$url || !$this->validateFeedUrl($url)) {
            $this->error = NotificationHelper::ERROR_WRONG_FEED;

            return null;
        }

        return $url;
    }

    /**
     * Create feed entity and persist it
     *
     * @param $url
     *
     * @return Feed
     */
    private function createFeedProcess($url)
    {
        $feed = null;

        try {
            $this->initRss($url);
            if ($this->rss->get_title()) {
                $feed = new Feed();
                $feed->setUrl($url);
                $feed->setUrlHash(sha1($url));
                $feed->setTitle($this->rss->get_title());
                $web = ($this->rss->get_link()) ? $this->rss->get_link() : $this->rss->feed_url;
                $feed->setWebsite($web);
                $this->entityManager->persist($feed);
            } else {
                $this->error = NotificationHelper::ERROR_WRONG_FEED;
            }
        } catch (Exception $e) {
            $this->error = $e->getMessage();
        }

        return $feed;
    }

    /**
     * Get new items since last sync of feed
     *
     * @param array   $items
     * @param integer $dateSync
     *
     * @return array
     */
    private function getAllItemsSync($items, $dateSync)
    {
        $newItems = array();
        foreach ($items as $item) {
            if ($item->get_date('U') > $dateSync) {
                $newItems[] = $item;
            } else {
                break;
            }
        }

        return $newItems;
    }

    /**
     * Get necessary items to sync
     *
     * @param Feed $feed
     *
     * @return array
     */
    protected function getItemToSync(Feed $feed)
    {
        if (!$feed->getDateSync()) {
            //get last 25 items
            $newItems = $this->getLasItemsSync($this->rss->get_items());
        } else {
            //get all items since last sync
            $newItems = $this->getAllItemsSync($this->rss->get_items(), $feed->getDateSync());
        }

        return $newItems;
    }

    /**
     * Get last 25 items for new feed
     *
     * @param array $items
     *
     * @return array
     */
    private function getLasItemsSync($items)
    {
        $c        = 0;
        $newItems = array();
        foreach ($items as $item) {
            $newItems[] = $item;
            $c++;
            if ($c >= 25) {
                break;
            }
        }

        return $newItems;
    }

    /**
     * Fix url
     *
     * @param string $url
     *
     * @return array
     */
    private function fixUrl($url)
    {
        if (strpos($url, '://') === false) {
            $url = 'http://' . $url;
        } elseif (substr($url, 0, 5) == 'feed:') {
            $url = 'http:' . substr($url, 5);
        }

        //prepend slash if the URL has no slash in it
        // "http://www.example" -> "http://www.example/"
        if (strpos($url, '/', strpos($url, ':') + 3) === false) {
            $url .= '/';
        }

        if ($url != "http:///") {
            return $url;
        } else {
            return '';
        }
    }

    /**
     * Init RSS
     *
     * @param $url
     */
    protected function initRss($url)
    {
        $this->rss->set_feed_url($url);
        $this->rss->set_parser_class();
        $this->rss->get_raw_data();
        $this->rss->init();
    }

    /**
     * Remove unnecessary characters from url
     *
     * @param $url
     *
     * @return string
     */
    private function removeUnnecessaryCharactersUrl($url)
    {
        $checkSlash = substr("$url", -1);
        if ($checkSlash == "/") {
            $url = substr($url, 0, -1);
        }

        return $url;
    }

    /**
     * Validete feed's url
     *
     * @param string $url
     *
     * @return array
     */
    private function validateFeedUrl($url)
    {
        $parts = parse_url($url);

        return ($parts['scheme'] == 'http' || $parts['scheme'] == 'feed' || $parts['scheme'] == 'https');
    }

    /**
     * Update feed's data
     *
     * @param Feed $feed Feed
     *
     * @return array
     */
    public function updateFeedData(Feed $feed)
    {
        $error = null;

        if ($this->checkDataChanged($feed)) {
            $error = $this->beginUpdateFeedData($feed);
        } else {
            $error = NotificationHelper::ALERT_FEED_UPDATE_NOT_NEEDED;
        }
        $result['error'] = $error;

        return $result;
    }
}
