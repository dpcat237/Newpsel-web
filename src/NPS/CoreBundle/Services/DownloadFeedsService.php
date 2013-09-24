<?php
namespace NPS\CoreBundle\Services;

use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Entity\UserFeed;
use NPS\CoreBundle\Entity\UserItem;

/**
 * DownloadFeedsService
 */
class DownloadFeedsService
{
    /**
     * @var $cache Redis
     */
    private $cache;

    /**
     * @var $doctrine Doctrine
     */
    private $doctrine;

    /**
     * @var $entityManager Entity Manager
     */
    private $entityManager;
    
    /**
     * @var $itemS ItemService
     */
    private $itemS;

    /**
     * @var $rss SimplePie RSS
     */
    private $rss;

    /**
     * @var $error Last process error
     */
    private $error = null;

    /**
     * @param Doctrine $doctrine
     * @param CacheService $cache
     * @param SimplePie $rss
     * @param ItemService $itemS
     */
    public function __construct($doctrine, $cache, $rss, $itemS)
    {
        $this->cache = $cache;
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->itemS = $itemS;
        $this->rss = $rss;
    }

    /**
     * Subscribe to feed
     * @param string $url
     * @param User   $user
     *
     * @throws Exception it's necessary set $rss
     * @return array
     */
    public function createFeed($url, $user = null)
    {
        $feed = null;
        $url = $this->fixUrl($url);
        $url = $this->removeUnnecessaryCharactersUrl($url);

        if (!$url || !$this->validateFeedUrl($url)) {
            $this->error = 302;
        }

        $feed = $this->getFeedByUrl($url);

        if (empty($this->error)) {
            $this->subscribeUser($user, $feed);
            $this->entityManager->flush();
        }

        $result['feed'] = $feed;
        $result['error'] = $this->error;

        return $result;
    }

    /**
     * Update feed's data
     * @param integer $feedId
     *
     * @return array
     */
    public function updateFeedData($feedId)
    {
        $error = null;
        $feedRepo = $this->doctrine->getRepository('NPSCoreBundle:Feed');
        $feed = $feedRepo->find($feedId);

        if ($feed instanceof Feed) {
            $this->rss->set_feed_url($feed->getUrl());
            $this->rss->set_parser_class();
            $this->rss->get_raw_data();
            $this->rss->init();
            $rssError = $this->rss->error();

            if (empty($rssError)) {
                $this->addNewItems($feed);
            } else {
                $error = $rssError;
            }
        } else {
            $error = 303;
        }
        $result['error'] = $error;

        return $result;
    }


    /**
     * Private functions
     */

    /**
     * Get Feed by url or create new one
     * @param $url
     *
     * @return Feed
     */
    private function getFeedByUrl($url)
    {
        $feedRepo = $this->doctrine->getRepository('NPSCoreBundle:Feed');
        $checkFeed = $feedRepo->checkExistFeedUrl($url);
        if (!$checkFeed instanceof Feed) {
            $feed = $this->createFeedProcess($url);
            if (!$feed instanceof Feed) {
                $this->error = $checkFeed['error'];
            }
        } else {
            $feed = $checkFeed;
        }

        return $feed;
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
            $this->rss->set_feed_url($url);
            $this->rss->set_parser_class();
            $this->rss->get_raw_data();
            $this->rss->init();

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
     * Add first items for just subscribed user
     * @param $feed
     * @param $user
     */
    private function addFirstItems($feed, $user)
    {
        if (count($feed->getItems()) < 1) {
            $this->updateFeedData($feed->getId());
        }
        $itemRepo = $this->doctrine->getRepository('NPSCoreBundle:Item');
        $items = $itemRepo->getLast($feed->getId());

        if (count($items)) {
            foreach ($items as $item) {
                $userItem = new UserItem();
                $userItem->setUser($user);
                $userItem->setItem($item);
                $userItem->setIsUnread(true);
                $this->entityManager->persist($userItem);
            }
            $this->entityManager->flush();
        }
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
     * Add news items of feed
     * @param $feed
     */
    private function addNewItems($feed)
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
     * Subscribe user to feed
     * @param User $user
     * @param Feed $feed
     */
    private function subscribeUser(User $user, Feed $feed)
    {
        if ($user instanceof User) {
            $feedRepo = $this->doctrine->getRepository('NPSCoreBundle:Feed');
            $feedSubscribed = $feedRepo->checkUserSubscribed($user->getId(), $feed->getId());
            if (!$feedSubscribed) {
                $userFeed = new UserFeed();
                $userFeed->setUser($user);
                $userFeed->setFeed($feed);
                $this->entityManager->persist($userFeed);
                $this->entityManager->flush();

                $feed->addUserFeed($userFeed); //just to Doctrine Feed know right now about new userFeed
                $this->addFirstItems($feed, $user);
            }
        }
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
}
