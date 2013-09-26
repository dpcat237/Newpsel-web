<?php
namespace NPS\CoreBundle\Helper;

use Symfony\Component\Templating\Helper\Helper;
use NPS\CoreBundle\Services\CrawlerService;

/**
 * Class for time functions
 */
class CrawlerHelper extends Helper
{
    /**
     * @var string
     */
    public $name = 'CrawlerHelper';


    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get array of supported feeds webs
     * @return array
     */
    static public function getSupportedFeeds()
    {
        $feeds = array(
            7,  //BBC news
            17, //Science news
            19, //Antena 3
            20, //OMG!
            26, //Mobile review
        );

        return $feeds;
    }

    /**
     * Crawling process for BBC news - #7
     * @param CrawlerService $service
     * @param string $itemUrl
     *
     * @return string
     */
    static public function process7(CrawlerService $service, $itemUrl)
    {
        $crawler = $service->getItemPage($itemUrl);
        $content = $crawler->filter('.layout-block-a');
        $content = explode('<!--Related hypers and stories -->', $content->html());

        return $content[0];
    }

    /**
     * Crawling process for Science news - #17
     * @param CrawlerService $service
     * @param string $itemUrl
     * @param string $itemContent
     *
     * @return string
     */
    static public function process17(CrawlerService $service, $itemUrl, $itemContent)
    {
        $itemContent = "<p>$itemContent</p>";
        $crawler = $service->getItemPage($itemUrl);
        $content = $crawler->filter('#maincol');
        $infoText = $content->filter('p.infotext');
        $content = explode('<!-- social media btns -->', $content->html());
        $articleContent = $itemContent.$content[0].$infoText->html();

        return $articleContent;
    }

    /**
     * Crawling process for Antena 3 - #19
     * @param CrawlerService $service
     * @param string $itemUrl
     * @param string $itemContent
     *
     * @return string
     */
    static public function process19(CrawlerService $service, $itemUrl, $itemContent)
    {
        $itemContent = "<p>$itemContent</p>";
        $crawler = $service->getItemPage($itemUrl);
        $content = $crawler->filter('div.mod_texto');
        $articleContent = $itemContent.$content->html();

        return $articleContent;
    }

    /**
     * Crawling process for OMG! Ubuntu! - #20
     * @param CrawlerService $service
     * @param string $itemUrl
     *
     * @return string
     */
    static public function process20(CrawlerService $service, $itemUrl)
    {
        $crawler = $service->getItemPage($itemUrl);
        $content = $crawler->filter('div.entry-content');

        return $content->html();
    }

    /**
     * Crawling process for Mobile review - #26
     * @param CrawlerService $service
     * @param string $itemUrl
     *
     * @return string
     */
    static public function process26(CrawlerService $service, $itemUrl)
    {
        $content =  file_get_contents($itemUrl);
        $content = explode('<div id="mainbanner">', $content);

        return $content[1];
    }
}
