<?php
/**
 * Created by JetBrains PhpStorm.
 * User: denys
 * Date: 4/12/13
 * Time: 9:39 PM
 * To change this template use File | Settings | File Templates.
 */

namespace NPS\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use NPS\CoreBundle\Services\CrawlerService,
    NPS\CoreBundle\Services\CacheService;
use NPS\CoreBundle\Entity\Item;

/**
 * Class ItemCrawlerCommand
 *
 * @package NPS\CoreBundle\Command
 */
class ItemCrawlerCommand extends ContainerAwareCommand
{
    private $feedId = 0;
    
    /**
     * configure of ItemCrawlerCommand
     */
    protected function configure()
    {
        $this
            ->setName('item:crawling')
            ->setDescription('Crawling web for incomplete articles')
            ->addArgument(
                'user',
                InputArgument::OPTIONAL,
                'Specify user id'
            );
    }

    /**
     * Synchronize all feeds
     * @param InputInterface  $input  Input Interface
     * @param OutputInterface $output Output Interface
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $log = $container->get('logger');
        $userId =(is_numeric($input->getArgument('user')))? $input->getArgument('user') : null;

        $log->info('*** Start crawling uncompleted articles ***');

        $laterItemRepo = $doctrine->getRepository('NPSCoreBundle:LaterItem');
        $laterItems = $laterItemRepo->getItemForCrawling($userId);

        if (count($laterItems)) {
            $this->iterateItemsForCrawling($container, $laterItems);
        }

        $log->info('*** Crawling finished ***');
    }

    /**
     * Make command process
     * @param ContainerInterface $container  ContainerInterface
     * @param array              $laterItems array of later items
     */
    private function iterateItemsForCrawling(ContainerInterface $container, $laterItems)
    {
        $crawler = $container->get('crawler');
        $cache = $container->get('server_cache');
        $cacheKey = 'crawledItem_';

        foreach ($laterItems as $laterItem) {
            $item = $laterItem->getUserItem()->getItem();
            if (!$cache->get($cacheKey.$item->getId())) {
                $this->makeCrawling($crawler, $cache, $cacheKey, $item);
            }
            continue;
        }
    }

    /**
     * Make crawling process
     * @param CrawlerService $crawler  CrawlerService
     * @param CacheService   $cache    CacheService
     * @param string         $cacheKey cache key
     * @param Item           $item     Item
     */
    private function makeCrawling(CrawlerService $crawler, CacheService $cache, $cacheKey, Item $item)
    {
        $sleepHidden = "sleep";
        if ($this->feedId == $item->getFeed()->getId()) {
            $sleepHidden(30);
        }

        if ($completeContent = $crawler->getCompleteContent($item->getLink(), $item->getContent(), $item->getFeedId())) {
            $cache->setex($cacheKey.$item->getId(), 2592000, $completeContent);
            $this->feedId = $item->getFeed()->getId();
        }
    }
}