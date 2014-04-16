<?php
namespace NPS\CoreBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Predis\Client;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use NPS\CoreBundle\Entity\Filter;
use NPS\FrontendBundle\Services\SystemNotificationService,
    NPS\CoreBundle\Services\UserWrapper;
use NPS\CoreBundle\Entity\Later;

/**
 * FilteringManager
 */
class FilteringManager
{
    /**
     * @var Doctirne Registry
     */
    private $doctrine;

    /**
     * @var Redis Client
     */
    private $redis;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var array
     */
    private $userFilterFeed = array();

    /**
     * @param Registry $doctrine Doctirne Registry
     * @param Client   $redis    Redis Client
     * @param Logger   $logger   Logger
     */
    public function __construct(Registry $doctrine, Client $redis, Logger $logger)
    {
        $this->doctrine = $doctrine;
        $this->redis = $redis;
        $this->logger = $logger;
    }

    /**
     * Add id of feed in which user has filter
     *
     * @param int $userId
     * @param int $feedId
     */
    private function addUserFilterFeed($userId, $feedId)
    {
        if (!isset($this->userFilterFeed[$userId]['feeds'])) {
            $this->userFilterFeed[$userId]['feeds'][] = $feedId;

            return;
        }

        if (!in_array($feedId, $this->userFilterFeed[$userId]['feeds'])) {
            $this->userFilterFeed[$userId]['feeds'][] = $feedId;
        }
    }

    /**
     * Check if user has filters for specified feed
     *
     * @param int    $userId
     * @param int    $feedId
     * @param string $filterName
     *
     * @return bool
     */
    public function checkUserFeedFilter($userId, $feedId, $filterName)
    {
        $cacheKey = 'nps.filter-user-filter.feed#'.$userId;
        $feedsData = $this->redis->get($cacheKey);
        $feedsIds = explode(',', $feedsData);
        if (!in_array($feedId, $feedsIds)) {
            return false;
        }

        $feedsFilters = $this->getFeedFilterCache($feedId, $filterName);
        foreach ($feedsFilters as $feedsFilter) {
            if ($feedsFilter['user_id'] == $userId && $filterName == 'to.label') {
                return $feedsFilter['later_id'];
            }

            if ($feedsFilter['user_id'] == $userId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set feed's filters data to redis
     * Before clear redis key previous value
     *
     * @param array $feedData
     */
    private function setFeedFilterCache($feedData)
    {
        $cacheKey = 'nps.filter-to.label-feed#'.$feedData['id'];
        $this->redis->del($cacheKey);

        foreach ($feedData['filterFeeds'] as $filterFeed) {
            $cacheData = array(
                'filter_id' => $filterFeed['filter']['id'],
                'user_id' => $filterFeed['filter']['user']['id'],
                'later_id' => $filterFeed['filter']['later']['id'],
            );
            $dataString = json_encode($cacheData);
            $this->redis->rpush($cacheKey, $dataString);

            //add id of feed in which user has filter
            $this->addUserFilterFeed($filterFeed['filter']['user']['id'], $feedData['id']);
        }
    }

    /**
     * Get feed filters from cache
     *
     * @param int    $feedId
     * @param string $filterName
     *
     * @return array
     */
    public function getFeedFilterCache($feedId, $filterName)
    {
        $cacheKey = 'nps.filter-'.$filterName.'-feed#'.$feedId;
        $feedsFiltersData = $this->redis->lrange($cacheKey, 0, -1 );
        $feedsFilters = array();
        foreach ($feedsFiltersData as $feedsFilterData) {
            $feedsFilters[] = json_decode($feedsFilterData, true);
        }

        return $feedsFilters;
    }

    /**
     * Set ids of feeds in which user have filter to redis
     */
    private function setUserFilterFeedCache()
    {
        //remove previous data
        foreach ($this->redis->keys('nps.filter-user-filter.feed*') as $key) {
            $this->redis->del($key);
        }

        //set new keys
        foreach ($this->userFilterFeed as $userId => $user) {
            $cacheKey = 'nps.filter-user-filter.feed#'.$userId;
            $cacheData = implode(', ', $user['feeds']);
            $this->redis->set($cacheKey, $cacheData);
        }
    }

    /**
     * Update filters in cache
     */
    public function updateFilterCache()
    {
        $feedRepo = $this->doctrine->getRepository('NPSCoreBundle:Feed');
        $feeds = $feedRepo->getFeedsFiltersForCache('to.label');

        foreach ($feeds as $feed) {
            $this->setFeedFilterCache($feed);
        }

        $this->setUserFilterFeedCache();
    }
}
