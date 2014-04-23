<?php
namespace NPS\CoreBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry;
use HTMLPurifier,
    HTMLPurifier_Config;
use Symfony\Component\DomCrawler\Crawler,
    Goutte\Client;
use Symfony\Component\Process\Process;
use NPS\CoreBundle\Helper\CrawlerHelper;
use Mmoreram\RSQueueBundle\Services\Producer;

/**
 * CrawlerService
 */
class CrawlerService
{
    /**
     * @var Doctrine
     */
    private $doctrine;

    /**
     * @var Entity Manager
     */
    private $entityManager;

    /**
     * @var HTMLPurifier
     */
    private $purifier;

    /**
     * @var Producer
     */
    private $rsqueue;


    /**
     * @param Registry $doctrine Doctrine Registry
     * @param Producer $rsqueue  RQ queue producer
     */
    public function __construct(Registry $doctrine, Producer $rsqueue)
    {
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->rsqueue = $rsqueue;

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
     *
     * @param string $itemUrl
     * @param string $itemContent
     * @param int    $feedId
     *
     * @return null
     */
    public function getCompleteContent($itemUrl, $itemContent, $feedId)
    {
        $complete = null;
        if ($feedId && $this->checkSpecial($feedId)) {
            $complete = $this->callSpecificCrawling($feedId, $itemUrl, $itemContent);
        } else {
            $complete = $this->executeGenericCrawling($itemUrl, $itemContent);
        }

        return $complete;
    }

    /**
     * Try get content with specific crawling
     *
     * @param int    $feedId
     * @param string $itemUrl
     * @param string $itemContent
     *
     * @return null
     */
    private function callSpecificCrawling($feedId, $itemUrl, $itemContent)
    {
        try {
            $processName = 'process'.$feedId;
            $complete = CrawlerHelper::$processName($this, $itemUrl, $itemContent);
        } catch (\Exception $e) {
            return null;
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
     * @param Crawler $crawler     Crawler
     * @param string  $match       string
     * @param string  $itemContent string
     *
     * @return null
     */
    public function checkFilter(Crawler $crawler, $match, $itemContent)
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
     * @param Crawler $node Crawler
     *
     * @return null
     */
    private function getNodeParents(Crawler $node)
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
     * Add crawling process to queue
     *
     * @param string $userId
     */
    public function executeCrawling($userId = null)
    {
        $this->rsqueue->produce("crawler", $userId);
    }
}
