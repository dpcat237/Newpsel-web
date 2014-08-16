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
use NPS\CoreBundle\Services\Entity\LaterService;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;


/**
 * LabelApiService
 */
class LabelApiService
{
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
     * @param Registry                      $doctrine         Doctrine Registry
     * @param SecureService                 $secure           SecureService
     * @param LaterService                  $labelService     LaterService
     * @param Client                        $cache            Redis Client
     * @param ContainerAwareEventDispatcher $eventDispatcher  ContainerAwareEventDispatcher
     */
    public function __construct(Registry $doctrine, SecureService $secure, LaterService $labelService, Client $cache, ContainerAwareEventDispatcher $eventDispatcher)
    {
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
        $this->labelService = $labelService;
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
        if (!$user instanceof User) {
            $responseData = array(
                'response' => $response,
                'labels' => $labels
            );

            return $responseData;
        }

        $redisKey = RedisConstants::LABEL_TREE.'_'.$user->getId();
        $labelsJson = $this->cache->get($redisKey);
        if (!strlen($labelsJson)) {
            $this->updateLabelsTree($user->getId());
            $labelsJson = $this->cache->get($redisKey);
        }
        $labels = json_decode($labelsJson, true);
        $responseData = array(
            'response' => true,
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
     * Update labels tree in cache
     *
     * @param int $userId
     */
    public function updateLabelsTree($userId)
    {
        $labels = array();
        $redisKey = RedisConstants::LABEL_TREE.'_'.$userId;
        $labelsData = $this->doctrine->getRepository('NPSCoreBundle:Later')->getUserLabel($userId);

        //prepare labels for api
        foreach ($labelsData as $lab) {
            $label['id'] = $lab->getId();
            $label['name'] = $lab->getName();
            $labels[] = $label;
        }

        $labelsJson = json_encode($labels);
        $this->cache->set($redisKey, $labelsJson);
    }
}
