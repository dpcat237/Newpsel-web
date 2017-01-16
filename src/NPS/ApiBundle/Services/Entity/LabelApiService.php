<?php

namespace NPS\ApiBundle\Services\Entity;

use Doctrine\ORM\EntityManager;
use NPS\CoreBundle\Constant\RedisConstants;
use NPS\CoreBundle\Entity\Later;
use NPS\CoreBundle\Event\LabelModifiedEvent;
use NPS\CoreBundle\NPSCoreEvents;
use NPS\CoreBundle\Repository\LaterRepository;
use Predis\Client;
use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Constant\EntityConstants;
use NPS\CoreBundle\Services\Entity\LaterService;
use NPS\CoreBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class LabelApiService
 *
 * @package NPS\ApiBundle\Services\Entity
 */
class LabelApiService
{
    /** @var $entityManager EntityManager */
    protected $entityManager;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var LaterService */
    private $labelService;

    /** @var SecureService */
    private $secure;

    /** @var Client */
    private $cache;

    /** @var Boolean */
    private $modified = false;

    /** @var LaterRepository */
    protected $tagRepository;

    /**
     * LabelApiService constructor.
     *
     * @param EntityManager $entityManager
     * @param SecureService $secure
     * @param LaterService $labelService
     * @param Client $cache
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EntityManager $entityManager, SecureService $secure, LaterService $labelService, Client $cache, EventDispatcherInterface $eventDispatcher)
    {
        $this->entityManager   = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->labelService    = $labelService;
        $this->secure          = $secure;
        $this->cache           = $cache;

        $this->tagRepository = $entityManager->getRepository(Later::class);
    }

    /**
     * Get user labels from app key for Chrome api
     *
     * @param $appKey
     *
     * @return array
     */
    public function getUserLabels($appKey)
    {
        $response = false;
        $labels   = [];
        $user     = $this->secure->getUserByDevice($appKey);
        if (!$user instanceof User) {
            $responseData = [
                'response' => $response,
                'labels'   => $labels,
            ];

            return $responseData;
        }

        $redisKey   = RedisConstants::LABEL_TREE . '_' . $user->getId();
        $labelsJson = $this->cache->get($redisKey);
        if (!strlen($labelsJson)) {
            $this->updateLabelsTree($user->getId());
            $labelsJson = $this->cache->get($redisKey);
        }
        $labels       = json_decode($labelsJson, true);
        $responseData = array(
            'response' => true,
            'labels'   => $labels
        );

        return $responseData;
    }

    /**
     * Sync labels
     *
     * @param User  $user
     * @param array $apiLabels
     *
     * @return array
     */
    public function syncLabels(User $user, array $apiLabels)
    {
        $error  = false;
        $labels = [];
        if (empty($error)) {
            $dbLabels = $this->tagRepository->getUserLabelsApi($user->getId());
            $labels   = $this->processLabelsSync($dbLabels, $apiLabels, $user);
        }
        $responseData = [
            'error'  => $error,
            'tags' => $labels,
        ];

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
        $labels         = [];
        $this->modified = false;
        //first sync to device
        if (!count($apiLabels)) {
            foreach ($dbLabels as $dbLabel) {
                $dbLabel['status'] = EntityConstants::STATUS_NEW;
                $labels[]          = $dbLabel;
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
                $label              = $this->labelService->createLabel($user, $apiLabel['name'], $apiLabel['date_up']);
                $apiLabel['api_id'] = $label->getId();
                $apiLabel['status'] = EntityConstants::STATUS_CHANGED;
                $labels[]           = $apiLabel;
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
     *
     * @return null
     */
    private function processChangedLabel($dbLabel, $apiLabel)
    {
        if ($dbLabel['date_up'] > $apiLabel['date_up']) {
            $dbLabel['status'] = EntityConstants::STATUS_CHANGED;
            $dbLabel['id']     = $apiLabel['id'];

            return $dbLabel;
        }
        if ($dbLabel['date_up'] < $apiLabel['date_up']) {
            $this->tagRepository->updateLabel($apiLabel['api_id'], $apiLabel['name'], $apiLabel['date_up']);
            $this->modified = true;
            $dbLabel['name'] = $apiLabel['name'];
            $dbLabel['date_up'] = $apiLabel['date_up'];

            return $dbLabel;
        }

        return null;
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
        $labels = [];
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
                $changedLabel = $this->processChangedLabel($dbLabel, $apiLabel);
                if (!empty($changedLabel)) {
                    $labels[] = $changedLabel;
                }

                unset($apiLabels[$keyApi]);
                $found = true;
                break;
            }
            if (!$found) {
                $dbLabel['status'] = EntityConstants::STATUS_NEW;
                $labels[]          = $dbLabel;
            }
        }

        return [$apiLabels, $labels];
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
        $labels        = [];
        $deletedLabels = $this->cache->get(RedisConstants::LABEL_DELETED . "_" . $user->getId());
        if (empty($deletedLabels)) {
            return array($apiLabels, $labels);
        }

        $deletedLabels = explode(',', $deletedLabels);
        foreach ($deletedLabels as $deletedLabel) {
            foreach ($apiLabels as $key => $apiLabel) {
                if ($apiLabel['api_id'] == $deletedLabel) {
                    $apiLabel['status'] = EntityConstants::STATUS_DELETED;
                    $labels[]           = $apiLabel;
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
        $labels     = [];
        $redisKey   = RedisConstants::LABEL_TREE . '_' . $userId;
        $labelsData = $this->tagRepository->getUserLabel($userId);

        //prepare labels for api
        foreach ($labelsData as $lab) {
            $label['id']   = $lab->getId();
            $label['name'] = $lab->getName();
            $labels[]      = $label;
        }

        $labelsJson = json_encode($labels);
        $this->cache->set($redisKey, $labelsJson);
    }
}
