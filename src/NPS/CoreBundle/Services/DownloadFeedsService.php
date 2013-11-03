<?php
namespace NPS\CoreBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Predis\Client;
use \SimplePie;
use NPS\CoreBundle\Entity\Feed,
    NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Services\Entity\FeedService,
    NPS\CoreBundle\Services\Entity\FeedHistoryService,
    NPS\CoreBundle\Services\Entity\ItemService;

/**
 * DownloadFeedsService
 */
class DownloadFeedsService
{
    CONST REDIS_KEY = 'feed';

    /**
     * @var Doctrine
     */
    private $doctrine;

    /**
     * @var Entity Manager
     */
    private $entityManager;

    /**
     * @var FeedService
     */
    private $feedS;

    /**
     * @var FeedHistoryService
     */
    private $feedHistoryS;

    /**
     * @var ItemService
     */
    private $itemS;

    /**
     * @var SimplePie RSS
     */
    private $rss;

    /**
     * @var Redis Client
     */
    private $redis;

    /**
     * @var Last process error
     */
    private $error = null;

    /**
     * @param Registry           $doctrine     Doctrine Registry
     * @param SimplePie          $rss          SimplePie
     * @param Client             $redis        Redis Client
     * @param FeedService        $feed         FeedService
     * @param ItemService        $itemS        ItemService
     * @param FeedHistoryService $feedHistoryS FeedHistoryService
     */
    public function __construct(Registry $doctrine, SimplePie $rss, Client $redis, FeedService $feed, ItemService $itemS, FeedHistoryService $feedHistoryS)
    {
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->rss = $rss;
        $this->redis = $redis;
        $this->feedS = $feed;
        $this->itemS = $itemS;
        $this->feedHistoryS = $feedHistoryS;
    }

    /**
     * Create feed if necessary and subscribe to it
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
            $result['feed'] = null;
            $result['error'] = $this->error;

            return $result;
        }

        $feed = $this->feedS->checkFeedByUrl($url);
        if ($feed instanceof Feed) {
            $this->feedS->subscribeUser($user, $feed);
            $this->itemS->addLastItemsNewUser($user, $feed);
        } else {
            $feed = $this->createFeed($url, $user);
        }

        $result['feed'] = $feed;
        $result['error'] = $this->error;

        return $result;
    }

    /**
     * Add news items of feed
     * @param Feed $feed Feed
     */
    private function addNewItems(Feed $feed)
    {
        if (!$feed->getDateSync()) {
            //get last 25 items
            $newItems = $this->getItemNew($this->rss->get_items());
        } else {
            //get all items since last sync
            $newItems = $this->getItemSync($this->rss->get_items(), $feed->getDateSync());
        }

        if (count($newItems)) {
            foreach ($newItems as $newItem) {
                $this->itemS->addItem($newItem, $feed);
            }

            //update last sync data
            $feed->setDateSync();
            $this->entityManager->persist($feed);
            $this->entityManager->flush();
        }
    }

    /**
     * Begin update process of feed's data
     *
     * @param Feed $feed
     *
     * @return array|null|string
     */
    protected function beginUpdateFeedData(Feed $feed)
    {
        $error = null;
        $this->initRss($feed->getUrl());
        $rssError = $this->rss->error();

        if (empty($rssError)) {
            $this->addNewItems($feed);
        } else {
            $error = $rssError;
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
        $currentHash = sha1_file($feed->getUrl());
        if ($currentHash == $this->redis->hget(self::REDIS_KEY, "feed_".$feed->getId()."_data_hash")) {
            $this->feedHistoryS->dataIsSame($feed);

            return false;
        }
        $this->redis->hset(self::REDIS_KEY, "feed_".$feed->getId()."_data_hash", $currentHash);
        $this->feedHistoryS->dataChanged($feed);

        return true;
    }

    /**
     * Create new feed, subscribe an user and update feed's data
     *
     * @param $url
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
     * @param $url
     *
     * @return Feed
     */
    private function createFeedProcess($url){
        $feed = null;

        try {
            $this->initRss($url);
            if ($this->rss->get_title()) {
                $feed = new Feed();
                $feed->setUrl($url);
                $feed->setUrlHash(sha1($url));
                $feed->setTitle($this->rss->get_title());
                $feed->setWebsite($this->rss->get_link());
                $feed->setLanguage($this->rss->get_language());
                $feed->setDateChange();
                $this->entityManager->persist($feed);
            } else {
                $this->error = NotificationHelper::ERROR_WRONG_FEED;
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();

        }

        return $feed;
    }

    /**
     * Get last 25 items for new feed
     * @param array $items
     *
     * @return array
     */
    private function getItemNew($items)
    {
        $c = 0;
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
     * Get new items since last sync of feed
     * @param array   $items
     * @param integer $dateSync
     *
     * @return array
     */
    private function getItemSync($items, $dateSync)
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
     * Fix url
     * @param string $url
     *
     * @return array
     */
    private function fixUrl($url)
    {
        if (strpos($url, '://') === false) {
            $url = 'http://' . $url;
        } else if (substr($url, 0, 5) == 'feed:') {
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
