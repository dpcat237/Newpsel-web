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
use Symfony\Component\DomCrawler\Crawler,
    Goutte\Client;
use NPS\CoreBundle\Helper\CrawlerHelper;

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
     * @param InputInterface  $input  [description]
     * @param OutputInterface $output [description]
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cacheKey = 'crawledItem_';
        $container = $this->getContainer();
        $crawler = $container->get('crawler');
        $doctrine = $container->get('doctrine');
        $log = $container->get('logger');
        $cache = $container->get('server_cache');
        $userId =(is_numeric($input->getArgument('user')))? $input->getArgument('user') : null;
        $feedId = 0;

        $log->info('*** Start crawling un full articles ***');

        /* TODO:
        $id = 1;
        $testn = 'process'.$id;
        $test = CrawlerHelper::$testn();
        echo "\ntut: ".$test; echo "\n\n"; exit();*/

        $laterItemRepo = $doctrine->getRepository('NPSCoreBundle:LaterItem');
        $laterItems = $laterItemRepo->getItemForCrawling($userId);

        if (count($laterItems)) {
            foreach ($laterItems as $laterItem) {
                $item = $laterItem->getUserItem()->getItem();
                if (!$cache->get($cacheKey.$item->getId())) {
                    if ($feedId == $item->getFeed()->getId()) {
                        sleep(30);
                    }
                    echo "\ntuta: save item: ".$item->getId(); echo "\n\n";
                    if ($completeContent = $crawler->getCompleteContent($item->getLink(), $item->getContent())) {
                        $cache->setex($cacheKey.$item->getId(), 2592000, $completeContent);

                        echo "\ntutb: saved item: ".$item->getId(); echo "\n\n";
                        //echo "\ntut: oki"; echo "\n\n"; exit();
                        $feedId = $item->getFeed()->getId();
                    }
                }

                continue;
            }
        }

        //echo "\ntut: end"; echo "\n\n"; exit();

        $log->info('*** Crawling finished ***');
    }
}