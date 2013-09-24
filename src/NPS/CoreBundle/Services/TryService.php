<?php
namespace NPS\CoreBundle\Services;

use NPS\CoreBundle\Services\CrawlerService;
use Symfony\Component\DomCrawler\Crawler,
    Goutte\Client;

/**
 * TryService
 */
class TryService
{
    /**
     * @var $cache Redis
     */
    private $cache;

    /**
     * @var $crawler CrawlerService
     */
    private $crawler;

    /**
     * @var $doctrine Doctrine
     */
    private $doctrine;

    /**
     * @var $entityManager Entity Manager
     */
    private $entityManager;

    /**
     * @var $rss SimplePie RSS
     */
    private $rss;

    /**
     * @param Doctrine       $doctrine Doctrine
     * @param CacheService   $cache    Redis service
     * @param CrawlerService $crawler  Crawler service
     * @param SimplePie      $rss      Simple Pie object
     */
    public function __construct($doctrine, $cache, CrawlerService $crawler, $rss)
    {
        $this->cache = $cache;
        $this->crawler = $crawler;
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->rss = $rss;
    }

    /**
     * Get feed's items
     * @param $link
     *
     * @return mixed
     */
    public function getFeedItems($link)
    {
        $this->rss->set_feed_url($link);
        $this->rss->set_parser_class();
        $this->rss->get_raw_data();
        $this->rss->init();
        $items = $this->rss->get_items();

        return $items;
    }

    /**
     * Show items of feed
     * @param $feedUrl
     *
     * @return mixed
     */
    public function showFeedItems($feedUrl)
    {
        $items = $this->getFeedItems($feedUrl);
        foreach ($items as $itemData) {
            echo "<br><br>title: ".$itemData->get_title();
            echo "<br><br>url: ".$itemData->get_link();
            echo "<br>content: ".$itemData->get_content();
            //get_description()
            echo "<div style='width: 100%; height: 1px; border-bottom: 1px solid 000;'></div>";
        }
        echo 'tut: '.count($items); exit();
    }

    /**
     * Show parsed specific item
     * @param $feedUrl
     * @param $itemTitle
     * @param $itemUrl
     */
    public function tryCrawledItem($feedUrl, $itemTitle, $itemUrl)
    {
        $completeContent = null;
        $items = $this->getFeedItems($feedUrl);
        foreach ($items as $itemData) {
            if ($itemData->get_title() == $itemTitle) {
                echo "<br><br>title: ".$itemData->get_title();
                echo "<br><br>url: ".$itemData->get_link();
                echo "<br>content: ".$itemData->get_content();
                echo "<div style='width: 100%; height: 1px; border-bottom: 1px solid 000;'></div><br><br>";

                $completeContent = $this->crawler->getCompleteContent($itemUrl, $itemData->get_content());
            } else {
                continue;
            }
        }

        if ($completeContent) {
            echo 'tut: oki <br>'.$completeContent; exit();
        } else {

        }
        echo 'tut: Ops :('; exit();
    }
}
