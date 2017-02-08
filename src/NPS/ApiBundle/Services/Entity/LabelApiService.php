<?php

namespace NPS\ApiBundle\Services\Entity;

use Doctrine\ORM\EntityManager;
use NPS\CoreBundle\Constant\RedisConstants;
use NPS\CoreBundle\Entity\Later;
use NPS\CoreBundle\Entity\LaterItem;
use NPS\CoreBundle\Event\LabelModifiedEvent;
use NPS\CoreBundle\Helper\ArrayHelper;
use NPS\CoreBundle\NPSCoreEvents;
use NPS\CoreBundle\Repository\LaterItemRepository;
use NPS\CoreBundle\Repository\LaterRepository;
use Predis\Client;
use NPS\ApiBundle\Services\SecureService;
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
    /** @var LaterRepository */
    protected $tagRepository;
    /** @var LaterItemRepository */
    protected $tagItemRepository;
    /** @var array */
    protected $tagsNew = [];
    /** @var array */
    protected $tagsRemove = [];
    /** @var array */
    protected $tagsUpdate = [];

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
        $this->tagItemRepository = $entityManager->getRepository(LaterItem::class);
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
     * @param User  $user
     * @param array $apiTags
     *
     * @return array
     */
    public function syncTags(User $user, array $apiTags)
    {
        $dbTags = $this->tagRepository->getUserLabelsApi($user->getId());
        $this->checkTagsDifferences($dbTags, $apiTags);
        $this->updateTagsDb($user);

        return $this->tagRepository->getUserLabelsApi($user->getId());
    }

    /**
     * @param array $dbTags
     * @param array $apiTags
     */
    protected function checkTagsDifferences(array $dbTags, array $apiTags)
    {
        if (!count($apiTags)) {
            return;
        }

        $this->tagsNew = [];
        $this->tagsRemove = [];
        $this->tagsUpdate = [];
        $dbTags = ArrayHelper::moveContendUnderKey($dbTags, 'tag_id');
        foreach ($apiTags as $apiTag) {
            $tagId = $apiTag['tag_id'];
            if (!$tagId) {
                $this->tagsNew[] = $apiTag;
                continue;
            }

            if (!array_key_exists($tagId, $dbTags)) {
                continue;
            }

            if (!$apiTag['date_up']) {
                $this->tagsRemove[] = $apiTag;
                continue;
            }

            if ($apiTag['date_up'] > $dbTags[$tagId]['date_up']) {
                $this->tagsUpdate[] = $apiTag;
            }
        }
    }

    /**
     * @param User $user
     */
    protected function updateTagsDb(User $user)
    {
        $modified = false;
        if (count($this->tagsNew)) {
            foreach ($this->tagsNew as $tag) {
                $this->labelService->createLabel($user, $tag['name'], $tag['date_up']);
            }
            $modified = true;
        }

        if (count($this->tagsRemove)) {
            $tagsIds = ArrayHelper::getIdsFromArray($this->tagsRemove, 'tag_id');
            $this->tagItemRepository->removeTagItemByTags($tagsIds);
            $this->tagRepository->removeTags($tagsIds);
            $modified = true;
        }

        if (count($this->tagsUpdate)) {
            foreach ($this->tagsUpdate as $tag) {
                $this->tagRepository->updateLabel($tag['tag_id'], $tag['name'], $tag['date_up']);
            }
            $modified = true;
        }

        if ($modified) {
            $tagEvent = new LabelModifiedEvent($user->getId());
            $this->eventDispatcher->dispatch(NPSCoreEvents::LABEL_MODIFIED, $tagEvent);
        }
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
