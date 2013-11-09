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
 * Class FeedHistoryCommand
 *
 * @package NPS\CoreBundle\Command
 */
class FeedHistoryCommand extends ContainerAwareCommand
{
    /**
     * configure of FeedHistoryCommand
     */
    protected function configure()
    {
        $this
            ->setName('feeds:history')
            ->setDescription('From sync history calculate best interval to sync');
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
        $log = $container->get('logger');
        $doctrine = $container->get('doctrine');
        $feedHistoryService = $container->get('feed_history');

        $log->info('*** Start feeds history analyze ***');

        $feedRepo = $doctrine->getRepository('NPSCoreBundle:Feed');
        $feeds = $feedRepo->getFeedsToUpdateData();
        foreach ($feeds as $feed) {
            $feedHistoryService->updateSyncInterval($feed);
        }

        //remove old history
        $feedHistoryRepo = $doctrine->getRepository('NPSCoreBundle:FeedHistory');
        $feedHistoryRepo->removeOldHistory();

        $log->info('*** Feeds history process finished ***');
    }
}