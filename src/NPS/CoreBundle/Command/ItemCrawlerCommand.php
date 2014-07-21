<?php
namespace NPS\CoreBundle\Command;

use Guzzle\Http\Exception\CurlException;
use NPS\CoreBundle\Services\CrawlerManager;
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
    private $feedTime = array();

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
        $this->addQueue('crawler', 'executeCrawling');
    }

    /**
     * Synchronize all feeds
     * @param InputInterface  $input  Input Interface
     * @param OutputInterface $output Output Interface
     * @param int             $userId User id, in case of null will get all users
     *
     * @return int|null|void
     */
    protected function executeCrawling(InputInterface $input, OutputInterface $output, $userId)
    {
        $container = $this->getContainer();
        $cache = $container->get('snc_redis.default');
        $crawler = $container->get('nps.manager.crawler');
        $doctrine = $container->get('doctrine');
        $this->logger = $container->get('logger');
        $userId =(is_numeric($userId))? : null;

        $this->logger->info('*** Start crawling uncompleted articles ***');

        $laterItemRepo = $doctrine->getRepository('NPSCoreBundle:LaterItem');
        $laterItems = $laterItemRepo->getItemForCrawling($userId);

        if (count($laterItems)) {
            $this->iterateItemsForCrawling($crawler, $cache, $laterItems);
        }

        $this->logger->info('*** Crawling finished ***');
    }

    /**
     * Make command process
     *
     * @param CrawlerManager $crawler    CrawlerManager
     * @param Client         $cache      Client
     * @param array          $laterItems array of later items
     */
    private function iterateItemsForCrawling(CrawlerManager $crawler, Client $cache, $laterItems)
    {
        $cacheKey = 'crawledItem_';
        $notFoundKey = 'crawledNotFoundItem_';

        foreach ($laterItems as $laterItem) {
            if ($cache->get($cacheKey.$laterItem['item_id']) || $cache->get($notFoundKey.$laterItem['item_id'])) {
                continue;
            }
            $this->makeCrawling($crawler, $cache, $laterItem, $cacheKey, $notFoundKey);
        }
    }

    /**
     * Make crawling process
     * @param CrawlerManager $crawler     CrawlerManager
     * @param Client         $cache       Client
     * @param array          $laterItem   array
     * @param string         $cacheKey    cache key
     * @param string         $notFoundKey key of not found
     */
    private function makeCrawling(CrawlerManager $crawler, Client $cache, $laterItem, $cacheKey, $notFoundKey)
    {
        $sleepHidden = "sleep";
        if ($laterItem['feed_id'] && $this->checkWaitForCrawling($laterItem['feed_id'])) {
            $sleepHidden(30);
        }

        try {
            $completeContent = $crawler->getFullArticle($laterItem['link']);
        } catch (Exception $e) {
            $completeContent = null;
            $this->logger->err("makeCrawling item id: ".$laterItem['item_id']." Error: ".$e->getMessage());
        } catch (CurlException $e) {
            $this->logger->err("makeCrawling item id: ".$laterItem['item_id']." Error: ".$e->getMessage());
            $completeContent = null;
        }

        if (strlen($completeContent) > 10) {
            $cache->setex($cacheKey.$laterItem['item_id'], 2592000, $completeContent);
        } else {
            $cache->setex($notFoundKey.$laterItem['item_id'], 1296000, $laterItem['item_id']);
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
}