<?php
namespace NPS\CoreBundle\Services;

use HTMLPurifier,
    HTMLPurifier_Config;
use Symfony\Component\DomCrawler\Crawler,
    Goutte\Client;
use Symfony\Component\Process\Process;

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
     * Parse item's web and get full content
     * @param $itemUrl
     * @param $itemContent
     *
     * @return null
     */
    public function getCompleteContent($itemUrl, $itemContent)
    {
        $complete = null;
        $client = new Client();
        $crawler = $client->request('GET', $itemUrl);
        //echo 'tut: '.$crawler->html(); exit();

        preg_match_all('/[0-9a-z\s-]{12,30}/i', $itemContent, $matches);
        //echo "\ntut: found matches: ".count($matches); echo "\n\n";
        //echo '<pre>tut: '; print_r($matches); echo '</pre>'; exit();

        $found = null;
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
            //echo "\ntut: search text: ".$searchText; echo "\n\n";
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
        $origCount = round((strlen($itemContent) / 2), 0);

        if ($content && (strlen($content) > $origCount)) {
            return $content;
        }

        return null;
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
