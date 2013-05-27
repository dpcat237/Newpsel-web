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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class FeedCommand
 *
 * @package NPS\CoreBundle\Command
 */
class FeedCommand extends ContainerAwareCommand
{
    /**
     * configure of FeedCommand
     */
    protected function configure()
    {
        $this
            ->setName('feeds:sync')
            ->setDescription('Sync all feeds');
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
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        $log = $container->get('logger');
        $log->info('*** Start feeds sync ***');

        $feedRepo = $em->getRepository('NPSModelBundle:Feed');
        $rss = $container->get('fkr_simple_pie.rss');
        $cache = $container->get('server_cache');
        $feedRepo->setRss($rss);
        $feedRepo->setCache($cache);
        $feeds = $feedRepo->findAll();
        foreach ($feeds as $feed) {
            $feedRepo->updateFeedData($feed->getId());
        }

        //$date = date('Y-m-d H:i:s');
        //$output->writeln('*** Synchronized successfully ***');
        $log->info('*** Synchronized successfully ***');
    }
}