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
use Symfony\Component\Console\Input\InputInterface;
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
        $log = $container->get('logger');
        $downloadFeeds = $container->get('download_feeds');

        $log->info('*** Start feeds sync ***');
        $feedRepo = $container->get('doctrine')->getRepository('NPSCoreBundle:Feed');
        $feeds = $feedRepo->findBy(array('enabled' => true));

        foreach ($feeds as $feed) {
            $downloadFeeds->updateFeedData($feed->getId());
        }

        $log->info('*** Synchronized successfully ***');
    }
}