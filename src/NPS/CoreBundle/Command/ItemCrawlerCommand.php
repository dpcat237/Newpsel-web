<?php
namespace NPS\CoreBundle\Command;

use Guzzle\Http\Exception\CurlException;
use NPS\CoreBundle\Constant\QueueConstants;
use Predis\Client;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;
use Mmoreram\RSQueueBundle\Command\ConsumerCommand;

/**
 * Class ItemCrawlerCommand
 *
 * @package NPS\CoreBundle\Command
 */
class ItemCrawlerCommand extends ConsumerCommand
{
    /**
     * @var array
     */
    private $feedTime = array();

    /**
     * @var Client
     */
    private $cache;

    /**
     * @var Crawler
     */
    private $crawler;

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var LanguageDetectService
     */
    private $languageDetector;

    /**
     * @var Logger
     */
    private $logger;


    /**
     * configure of ItemCrawlerCommand
     */
    protected function configure()
    {
        $this
            ->setName('item:crawling')
            ->setDescription('Crawling web for incomplete articles');

        parent::configure();
    }

    /**
     * Relates queue name with appropriate method
     */
    public function define()
    {
        $this->addQueue(QueueConstants::ITEMS_CRAWLER, 'executeCrawling');
    }

    /**
     * Grab complete articles
     *
     * @param InputInterface  $input  Input Interface
     * @param OutputInterface $output Output Interface
     * @param int             $userId User id, in case of null will get all users
     *
     * @return int|null|void
     */
    protected function executeCrawling(InputInterface $input, OutputInterface $output, $userId)
    {
        $container = $this->getContainer();
        $this->cache = $container->get('snc_redis.default');
        $this->crawler = $container->get('nps.manager.crawler');
        $this->languageDetector = $container->get('nps.detector.language');
        $this->doctrine = $container->get('doctrine');
        $this->logger = $container->get('logger');
        $userId =(is_numeric((int) $userId))? $userId : null;

        $this->logger->info('*** Start crawling uncompleted articles ***');

        $laterItemRepo = $this->doctrine->getRepository('NPSCoreBundle:LaterItem');
        $laterItems = $laterItemRepo->getItemForCrawling($userId);

        if (count($laterItems)) {
            $this->iterateItemsForCrawling($laterItems);
        }

        $this->logger->info('*** Crawling finished ***');
    }

    /**
     * Make command process
     *
     * @param array          $laterItems array of later items
     */
    private function iterateItemsForCrawling($laterItems)
    {
        $cacheKey = 'crawledItem_';
        $notFoundKey = 'crawledNotFoundItem_';

        foreach ($laterItems as $laterItem) {
            if ($this->cache->get($cacheKey.$laterItem['item_id']) || $this->cache->get($notFoundKey.$laterItem['item_id'])) {
                continue;
            }
            $this->makeCrawling($laterItem, $cacheKey, $notFoundKey);
        }
    }

    /**
     * Make crawling process
     *
     * @param array  $laterItem   array
     * @param string $cacheKey    cache key
     * @param string $notFoundKey key of not found
     */
    private function makeCrawling($laterItem, $cacheKey, $notFoundKey)
    {
        $sleepHidden = "sleep";
        if ($laterItem['feed_id'] && $this->checkWaitForCrawling($laterItem['feed_id'])) {
            $sleepHidden(30);
        }

        try {
            $completeContent = $this->crawler->getFullArticle($laterItem['link']);
        } catch (Exception $e) {
            $completeContent = null;
            $this->logger->err("makeCrawling item id: ".$laterItem['item_id']." Error: ".$e->getMessage());
        } catch (CurlException $e) {
            $this->logger->err("makeCrawling item id: ".$laterItem['item_id']." Error: ".$e->getMessage());
            $completeContent = null;
        }

        if (strlen($completeContent) > 100) {
            $this->cache->setex($cacheKey.$laterItem['item_id'], 2592000, $completeContent);
            $this->detectArticleLanguage($laterItem, $completeContent);
        } else {
            $this->cache->setex($notFoundKey.$laterItem['item_id'], 1296000, $laterItem['item_id']);
        }
    }

    /**
     * Check if have to wait to make crawling
     *
     * @param int $feedId
     *
     * @return bool
     */
    private function checkWaitForCrawling($feedId)
    {
        if (!array_key_exists($feedId, $this->feedTime)) {
            $this->feedTime[$feedId] = time();

            return false;
        }

        $currentTime = time();
        $crawledTime = $this->feedTime[$feedId] + 30;
        $this->feedTime[$feedId] = time();
        if ($currentTime > $crawledTime) {
            return false;
        }

        return true;
    }

    /**
     * If it's shared item detect his language and save to data base
     *
     * @param array  $laterItem
     * @param string $completeContent
     */
    private function detectArticleLanguage($laterItem, $completeContent)
    {
        if ($laterItem['feed_id']) {
            return;
        }
        $textSample = substr(strip_tags($completeContent), 0, 500);
        $languageCode = $this->languageDetector->detectLanguage($textSample);
        $itemRepo = $this->doctrine->getRepository('NPSCoreBundle:Item');
        $itemRepo->addLanguage($laterItem['item_id'], $languageCode);
    }
}