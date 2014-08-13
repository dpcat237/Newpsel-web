<?php
namespace NPS\ApiBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\CoreBundle\Constant\RedisConstants;
use NPS\CoreBundle\Event\LabelModifiedEvent;
use NPS\CoreBundle\NPSCoreEvents;
use NPS\CoreBundle\Repository\LaterRepository;
use Predis\Client;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Constant\EntityConstants;
use NPS\CoreBundle\Entity\Later;
use NPS\CoreBundle\Helper\ArrayHelper;
use NPS\CoreBundle\Services\Entity\LaterService,
    NPS\CoreBundle\Services\Entity\LaterItemService;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Services\QueueLauncherService;


/**
 * LabelApiService
 */
class LabelApiService
{
    /**
     * @var QueueLauncherService
     */
    private $queueLauncher;

    /**
     * @var Doctrine Registry
     */
    private $doctrine;

    /**
     * @var ContainerAwareEventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var LaterService
     */
    private $labelService;

    /**
     * @var LaterItemService
     */
    private $labelItemService;

    /**
     * @var SecureService
     */
    private $secure;

    /**
     * @var Client
     */
    private $cache;

    /**
     * @var Boolean
     */
    private $modified = false;


    /**
     * @param QueueLauncherService          $queueLauncher    QueueLauncherService
     * @param Registry                      $doctrine         Doctrine Registry
     * @param SecureService                 $secure           SecureService
     * @param LaterService                  $labelService     LaterService
     * @param LaterItemService              $labelItemService LaterItemService
     * @param Client                        $cache            Redis Client
     * @param ContainerAwareEventDispatcher $eventDispatcher  ContainerAwareEventDispatcher
     */
    public function __construct(QueueLauncherService $queueLauncher, Registry $doctrine, SecureService $secure, LaterService $labelService, LaterItemService $labelItemService, Client $cache, ContainerAwareEventDispatcher $eventDispatcher)
    {
        $this->queueLauncher = $queueLauncher;
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
        $this->labelService = $labelService;
        $this->labelItemService = $labelItemService;
        $this->secure = $secure;
        $this->cache = $cache;
    }

    /**
     * Get user labels from app key for Chrome api
     * @param $appKey
     *
     * @return array
     */
    public function getUserLabels($appKey)
    {
        $response = false;
        $labels = array();

        $user = $this->secure->getUserByDevice($appKey);
        if ($user instanceof User) {
            $labelRepo = $this->doctrine->getRepository('NPSCoreBundle:Later');
            $orderBy = array('name' => 'ASC');
            $labelsData = $labelRepo->findByUser($user, $orderBy);

            //prepare labels for api
            foreach ($labelsData as $lab) {
                $label['id'] = $lab->getId();
                $label['name'] = $lab->getName();
                $labels[] = $label;
            }
            $response = true;
        }
        $responseData = array(
            'response' => $response,
            'labels' => $labels
        );

        return $responseData;
    }

    /**
     * Sync labels
     *
     * @param string $appKey
     * @param array  $apiLabels
     *
     * @return array
     */
    public function syncLabels($appKey, array $apiLabels)
    {
        $error = false;
        $labels = array();
        $user = $this->secure->getUserByDevice($appKey);
        if (!$user instanceof User) {
            $error = NotificationHelper::ERROR_NO_LOGGED;
        }

        if (empty($error)){
            $dbLabels = $this->doctrine->getRepository('NPSCoreBundle:Later')->getUserLabelsApi($user->getId());
            $labels = $this->processLabelsSync($dbLabels, $apiLabels, $user);
        }
        $responseData = array(
            'error' => $error,
            'labels' => $labels,
        );

        return $responseData;
    }

    /**
     * Process labels updating if necessary data in data base and get new data to return to API
     *
     * @param array $dbLabels
     * @param array $apiLabels
     * @param User  $user
     *
     * @return array
     */
    private function processLabelsSync(array $dbLabels, array $apiLabels, $user)
    {
        $labels = array();
        $this->modified = false;
        //first sync to device
        if (!count($apiLabels)) {
            foreach ($dbLabels as $dbLabel) {
                $dbLabel['status'] = EntityConstants::STATUS_NEW;
                $labels[] = $dbLabel;
            }

            return $labels;
        }

        //check if in server were deleted labels
        list($apiLabels, $deletedLabels) = $this->checkDeletedLabels($user, $apiLabels);
        if (count($deletedLabels)) {
            $labels = $deletedLabels;
        }

        //compare labels from API and server data base
        list($apiLabels, $changedLabels) = $this->compareSyncApiDb($dbLabels, $apiLabels);
        if (count($changedLabels)) {
            $labels = array_merge($labels, $changedLabels);
        }

        //if are new labels from API
        if (count($apiLabels)) {
            foreach ($apiLabels as $apiLabel) {
                $label = $this->labelService->createLabel($user, $apiLabel['name'], $apiLabel['date_up']);
                $apiLabel['api_id'] = $label->getId();
                $apiLabel['status'] = EntityConstants::STATUS_CHANGED;
                $labels[] = $apiLabel;
            }
            $this->modified = true;
        }

        //if it's necessary notify about changes other devices
        if ($this->modified) {
            //notify about modification
            $labelEvent = new LabelModifiedEvent($user->getId());
            $this->eventDispatcher->dispatch(NPSCoreEvents::LABEL_MODIFIED, $labelEvent);
        }

        return $labels;
    }

    /**
     * Compare last update dates and update label in proper place
     *
     * @param array           $dbLabel
     * @param array           $apiLabel
     * @param LaterRepository $labelRepo
     *
     * @return null
     */
    private function processChangedLabel($dbLabel, $apiLabel, LaterRepository $labelRepo)
    {
        if ($dbLabel['date_up'] > $apiLabel['date_up']) {
            $dbLabel['status'] = EntityConstants::STATUS_CHANGED;
            $dbLabel['id'] = $apiLabel['id'];

            return $dbLabel;
        }
        if ($dbLabel['date_up'] < $apiLabel['date_up']) {
            $labelRepo->updateLabel($apiLabel['api_id'], $apiLabel['name'], $apiLabel['date_up']);
            $this->modified = true;

            return null;
        }
    }

    /**
     * Compare labels from API and data base to find difference
     *
     * @param array $dbLabels
     * @param array $apiLabels
     *
     * @return array
     */
    private function compareSyncApiDb(array $dbLabels, array $apiLabels)
    {
        $labels = array();
        $labelRepo = $this->doctrine->getRepository('NPSCoreBundle:Later');

        foreach ($dbLabels as $dbLabel) {
            $found = false;
            foreach ($apiLabels as $keyApi => $apiLabel) {
                if ($dbLabel['api_id'] != $apiLabel['api_id']) {
                    continue;
                }

                //any change
                if ($dbLabel['date_up'] == $apiLabel['date_up']) {
                    unset($apiLabels[$keyApi]);
                    $found = true;
                    break;
                }

                //compare changes
                $changedLabel = $this->processChangedLabel($dbLabel, $apiLabel, $labelRepo);
                if (!empty($changedLabel)) {
                    $labels[] = $changedLabel;
                }

                unset($apiLabels[$keyApi]);
                $found = true;
                break;
            }
            if (!$found) {
                $dbLabel['status'] = EntityConstants::STATUS_NEW;
                $labels[] = $dbLabel;
            }
        }

        return array($apiLabels, $labels);
    }

    /**
     * Get deleted labels in server
     *
     * @param User  $user
     * @param array $apiLabels
     *
     * @return array
     */
    private function checkDeletedLabels(User $user, array $apiLabels)
    {
        $labels = array();
        $deletedLabels = $this->cache->get(RedisConstants::LABEL_DELETED."_".$user->getId());
        if (empty($deletedLabels)) {
            return array($apiLabels, $labels);
        }

        $deletedLabels = explode(',', $deletedLabels);
        foreach ($deletedLabels as $deletedLabel) {
            foreach ($apiLabels as $key => $apiLabel) {
                if ($apiLabel['api_id'] == $deletedLabel) {
                    $apiLabel['status'] = EntityConstants::STATUS_DELETED;
                    $labels[] = $apiLabel;
                    unset($apiLabels[$key]);
                    break;
                }
            }
        }

        return array($apiLabels, $labels);
    }

    /**
     * Sync later item from API to database to be read later
     *
     * @param array $appKey     login key
     * @param array $laterItems selected items to be read later
     *
     * @return array
     */
    public function syncLaterItemsApi($appKey, $laterItems)
    {
        $error = false;
        $result = false;

        $user = $this->secure->getUserByDevice($appKey);
        if (!$user instanceof User) {
            $error = NotificationHelper::ERROR_NO_LOGGED;
            $result = NotificationHelper::ERROR_NO_LOGGED;
        }

        if (empty($error) && is_array($laterItems) && count($laterItems)){
            $this->labelService->syncLaterItems($user->getId(), $laterItems);
            //get complete content for partial articles
            $this->queueLauncher->executeCrawling($user->getId());

            $result = NotificationHelper::OK;
        }
        $responseData = array(
            'error' => $error,
            'result' => $result,
        );

        return $responseData;
    }

    /**
     * Sync later item to API and update the reviewed items
     *
     * @param array $appKey       login key
     * @param array $dictateItems items for dictation
     * @param int   $limit        limit of dictations to sync
     *
     * @return array
     */
    public function syncDictateItems($appKey, $dictateItems, $limit)
    {
        $error = false;
        $result = array();

        $user = $this->secure->getUserByDevice($appKey);
        if (!$user instanceof User) {
            $error = NotificationHelper::ERROR_NO_LOGGED;
            $result = NotificationHelper::ERROR_NO_LOGGED;
        }

        list($unreadItems, $readItems) = ArrayHelper::separateBooleanArray($dictateItems, 'is_unread');
        if (empty($error) && is_array($readItems) && count($readItems)) {
            $this->doctrine->getRepository('NPSCoreBundle:LaterItem')->syncViewedLaterItems($readItems);
        }
        if (empty($error)) {
            $result = $this->labelItemService->getUnreadItemsApi($user->getPreference()->getReadLaterId(), $unreadItems, $limit);
        }
        $responseData = array(
            'error' => $error,
            'result' => $result,
        );

        return $responseData;
    }

    /**
     * Add page for Chrome api
     *
     * @param string $appKey
     * @param int    $labelId
     * @param string $webTitle
     * @param string $webUrl
     *
     * @return array
     */
    public function addPage($appKey, $labelId, $webTitle, $webUrl)
    {
        $response = false;
        $user = $this->secure->getUserByDevice($appKey);
        if ($user instanceof User) {
            $this->labelItemService->addPageToLater($user, $labelId, $webTitle, $webUrl, true);
            $response = true;
        }
        $this->queueLauncher->executeCrawling($user->getId());

        $responseData = array(
            'response' => $response
        );

        return $responseData;
    }
}
