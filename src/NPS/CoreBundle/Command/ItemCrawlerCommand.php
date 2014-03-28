<?php
namespace NPS\CoreBundle\Command;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;
use NPS\CoreBundle\Services\CrawlerService,
    NPS\CoreBundle\Services\CacheService;
use NPS\CoreBundle\Entity\Item;
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
        $cache = $container->get('server_cache');
        $crawler = $container->get('crawler');
        $doctrine = $container->get('doctrine');
        $log = $container->get('logger');
        $userId =(is_numeric($userId))? : null;

        $log->info('*** Start crawling uncompleted articles ***');

        $laterItemRepo = $doctrine->getRepository('NPSCoreBundle:LaterItem');
        $laterItems = $laterItemRepo->getItemForCrawling($userId);

        if (count($laterItems)) {
            $this->iterateItemsForCrawling($crawler, $cache, $laterItems);
        }

        $log->info('*** Crawling finished ***');
    }

    /**
     * Make command process
     * @param CrawlerService $crawler    CrawlerService
     * @param CacheService   $cache      CacheService
     * @param array          $laterItems array of later items
     */
    private function iterateItemsForCrawling(CrawlerService $crawler, CacheService $cache, $laterItems)
    {
        $cacheKey = 'crawledItem_';
        $notFoundKey = 'crawledNotFoundItem_';

        foreach ($laterItems as $laterItem) {
            $item = $laterItem->getUserItem()->getItem();
            if ($cache->get($cacheKey.$item->getId()) || $cache->get($notFoundKey.$item->getId())) {
                continue;
            }
            $this->makeCrawling($crawler, $cache, $item, $cacheKey, $notFoundKey);
        }
    }

    /**
     * Make crawling process
     * @param CrawlerService $crawler     CrawlerService
     * @param CacheService   $cache       CacheService
     * @param Item           $item        Item
     * @param string         $cacheKey    cache key
     * @param string         $notFoundKey key of not found
     */
    private function makeCrawling(CrawlerService $crawler, CacheService $cache, Item $item, $cacheKey, $notFoundKey)
    {
        $sleepHidden = "sleep";
        if ($this->checkWaitForCrawling($item->getFeed()->getId())) {
            $sleepHidden(30);
        }

        try {
            $completeContent = $crawler->getCompleteContent($item->getLink(), $item->getContent(), $item->getFeedId());
        } catch (Exception $e) {
            $completeContent = null;
        }

        if ($completeContent) {
            $cache->setex($cacheKey.$item->getId(), 2592000, $completeContent);
        } else {
            $cache->setex($notFoundKey.$item->getId(), 1296000, $item->getLink());
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
        if ($currentTime > $crawledTime) {
            return false;
        }

        return true;
    }
}