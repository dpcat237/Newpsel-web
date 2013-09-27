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
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ItemCrawlerCommand
 *
 * @package NPS\CoreBundle\Command
 */
class ItemCrawlerCommand extends ContainerAwareCommand
{
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
            $this->makeProcess($container, $laterItems);
        }

        $log->info('*** Crawling finished ***');
    }

    /**
     * Make command process
     * @param Container $container
     * @param array     $laterItems
     */
    private function makeProcess($container, $laterItems)
    {
        $crawler = $container->get('crawler');
        $cache = $container->get('server_cache');
        $cacheKey = 'crawledItem_';
        $feedId = 0;

        foreach ($laterItems as $laterItem) {
            $item = $laterItem->getUserItem()->getItem();
            if (!$cache->get($cacheKey.$item->getId())) {
                echo "\ntut enter: item: ".$item->getId().' feed: '.$item->getFeedId(); echo "\n\n";
                if ($feedId == $item->getFeed()->getId()) {
                    sleep(30);
                }
                if ($completeContent = $crawler->getCompleteContent($item->getLink(), $item->getContent(), $item->getFeedId())) {
                    $cache->setex($cacheKey.$item->getId(), 2592000, $completeContent);
                    $feedId = $item->getFeed()->getId();
                }
            }
            continue;
        }
    }
}