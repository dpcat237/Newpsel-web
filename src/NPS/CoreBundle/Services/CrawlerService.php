<?php
namespace NPS\CoreBundle\Services;

use HTMLPurifier,
    HTMLPurifier_Config;
use Symfony\Component\DomCrawler\Crawler,
    Goutte\Client;
use Symfony\Component\Process\Process;
use NPS\CoreBundle\Helper\CrawlerHelper;

/**
 * CrawlerService
 */
class CrawlerService
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
     * @var $purifier HTMLPurifier
     */
    private $purifier;

    /**
     * @param Doctrine     $doctrine
     * @param CacheService $cache
     */
    public function __construct($doctrine, $cache)
    {
        $this->cache = $cache;
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();

        if (empty($this->purifier)) {
            $config = HTMLPurifier_Config::createDefault();
            $this->purifier = new HTMLPurifier($config);
        }
    }

    /**
     * Check if this feed's items must be parsed
     * @param int $feedId Feed id
     *
     * @return bool
     */
    public function checkSpecial($feedId)
    {
        if (in_array($feedId, CrawlerHelper::getSupportedFeeds())) {
            return true;
        }

        return false;
    }

    /**
     * Parse item's web and get full content
     * @param string $itemUrl
     * @param string $itemContent
     * @param int    $feedId
     *
     * @return null
     */
    public function getCompleteContent($itemUrl, $itemContent, $feedId)
    {
        $complete = null;
        if ($this->checkSpecial($feedId)) {
            $processName = 'process'.$feedId;
            $complete = CrawlerHelper::$processName($this, $itemUrl, $itemContent);
        } else {
            $complete = $this->executeGenericCrawling($itemUrl, $itemContent);
        }

        return $complete;
    }

    /**
     * @param $itemUrl
     * @param $itemContent
     *
     * @return null
     */
    private function executeGenericCrawling($itemUrl, $itemContent)
    {
        $complete = null;
        $found = null;
        $crawler = $this->getItemPage($itemUrl);

        preg_match_all('/[0-9a-z\s-]{12,30}/i', $itemContent, $matches);
        foreach ($matches[0] as $match) {
            if ($complete = $this->checkFilter($crawler, $match, $itemContent)) {
                break;
            } else {
                continue;
            }
        }

        return $complete;
    }

    /**
     * Check if filter found something
     * @param $crawler
     * @param $match
     * @param $itemContent
     *
     * @return null
     */
    public function checkFilter($crawler, $match, $itemContent)
    {
        $fullContent = null;
        $result = trim($match);

        if (preg_match('/\s/', $result)) {
            $searchText = $result;
            $path = "//*[text()[contains(., '$searchText')]]";
            $filtered = $crawler->filterXPath($path);
            $fullContent = $this->checkFoundBigger($filtered, $itemContent);
        }

        return $fullContent;
    }

    /**
     * Check if found content is longer than original
     * @param $filtered
     * @param $itemContent
     *
     * @return null
     */
    private function checkFoundBigger($filtered, $itemContent)
    {
        $content = $this->getNodeParents($filtered);
        $origCount = round((strlen($itemContent) / 4), 0);
        if ($content && (strlen($content) > $origCount) && (!strstr($content, '<meta'))) {
            return $content;
        }

        return null;
    }

    /**
     * Get crawler of item web page
     * @param $itemUrl
     *
     * @return Crawler
     */
    public function getItemPage($itemUrl)
    {
        $client = new Client();
        $crawler = $client->request('GET', $itemUrl);

        return $crawler;
    }

    /**
     * Get content of parents if it was found
     * @param $node
     *
     * @return null
     */
    private function getNodeParents($node)
    {
        $content = null;
        try {
            $foundData = $node->parents();
            $content = $foundData->html();
        } catch (\Exception $e) {
            return null;
        }

        return $content;
    }

    /**
     * Run crawling process
     * @param string $userId
     */
    public function executeCrawling($userId = '')
    {
        $path = "php /var/www/nps/app/console item:crawling $userId > /dev/null &";
        $process = new Process($path);
        $process->run();
    }
}
