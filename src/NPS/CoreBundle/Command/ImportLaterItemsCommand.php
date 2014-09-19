<?php
namespace NPS\CoreBundle\Command;

use NPS\CoreBundle\Constant\QueueConstants;
use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;
use Mmoreram\RSQueueBundle\Command\ConsumerCommand;

/**
 * Class ImportLaterItemsCommand
 *
 * @package NPS\CoreBundle\Command
 */
class ImportLaterItemsCommand extends ConsumerCommand
{
    /**
     * configure of ItemCrawlerCommand
     */
    protected function configure()
    {
        $this
            ->setName('item:import')
            ->setDescription('Import later items');

        parent::configure();
    }

    /**
     * Relates queue name with appropriate method
     */
    public function define()
    {
        $this->addQueue(QueueConstants::IMPORT_LATER_ITEMS, 'executeImport');
    }

    /**
     * Import later items
     *
     * @param InputInterface  $input    Input Interface
     * @param OutputInterface $output   Output Interface
     * @param string          $redisKey redis key to later items
     *
     * @return int|null|void
     */
    protected function executeImport(InputInterface $input, OutputInterface $output, $redisKey)
    {
        $container = $this->getContainer();
        $logger = $container->get('logger');
        $cache = $container->get('snc_redis.default');
        $laterItemService = $container->get('nps.entity.later_item');

        $logger->info('*** Start import later items ***');

        $keyData = explode('_', $redisKey);
        $user = $container->get('doctrine')->getRepository('NPSCoreBundle:User')->find($keyData[1]);
        $labelId = $keyData[2];
        $json = $cache->get($redisKey);
        $items = json_decode($json, true);
        $cache->del($redisKey);
        if (!count($items)) {
            return;
        }

        foreach ($items as $item) {
            $laterItemService->importItem($user, $labelId, $item['title'], $item['url'], $item['date_add'], $item['is_article']);
        }
        $container->get('nps.launcher.queue')->executeCrawling($user->getId());

        $logger->info('*** Import items finished ***');
    }
}