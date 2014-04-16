<?php
/**
 * Created by Denys Pasishnyi.
 * Date: 4/12/13
 * Time: 9:39 PM
 */

namespace NPS\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class FeedSyncCommand
 *
 * @package NPS\CoreBundle\Command
 */
class FeedSyncCommand extends ContainerAwareCommand
{
    /**
     * configure of FeedSyncCommand
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
        $filter = $container->get('nps.manager.filter');
        $filter->updateFilterCache();

        $log->info('*** Start feeds sync ***');
        $feedRepo = $container->get('doctrine')->getRepository('NPSCoreBundle:Feed');
        $feeds = $feedRepo->getFeedsToUpdateData();

        foreach ($feeds as $feed) {
            $downloadFeeds->updateFeedData($feed);
        }

        $log->info('*** Synchronized successfully ***');
    }
}