<?php

namespace NPS\ApiBundle\Services\Entity;

use Doctrine\ORM\EntityManager;
use NPS\ApiBundle\DataTransformer\SavedItemTransformer;
use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Entity\LaterItem;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\ArrayHelper;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Repository\LaterItemRepository;
use NPS\CoreBundle\Services\Entity\LaterItemService;
use NPS\CoreBundle\Services\QueueLauncherService;

/**
 * LaterItemApiService
 */
class LaterItemApiService
{
    /** @var $entityManager EntityManager */
    protected $entityManager;
    /** @var LaterItemService */
    private $laterItem;
    /** @var LaterItemRepository */
    private $laterItemRepository;
    /** @var SecureService */
    private $secure;
    /** @var QueueLauncherService */
    private $queueLauncher;

    private $itamTageAdd = [];
    private $itamTageRemove = [];

    /**
     * LaterItemApiService constructor.
     *
     * @param EntityManager $entityManager
     * @param SecureService $secure
     * @param QueueLauncherService $queueLauncher
     * @param LaterItemService $laterItem
     */
    public function __construct(EntityManager $entityManager, SecureService $secure, QueueLauncherService $queueLauncher, LaterItemService $laterItem)
    {
        $this->entityManager = $entityManager;
        $this->laterItem     = $laterItem;
        $this->secure        = $secure;
        $this->queueLauncher = $queueLauncher;

        $this->laterItemRepository = $entityManager->getRepository(LaterItem::class);
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
        $user     = $this->secure->getUserByDevice($appKey);
        if ($user instanceof User) {
            $this->laterItem->addPageToLater($user, $labelId, $webTitle, $webUrl, true);
            $response = true;
        }
        $this->queueLauncher->executeCrawling($user->getId());

        $responseData = array(
            'response' => $response
        );

        return $responseData;
    }

    /**
     * Sync viewed later items and download unread later items
     *
     * @param array $unreadItemsIds  array of all items from API with basic information
     * @param array $tagsIds array of labels from which sync items
     * @param int   $limit  max quantity of items to sync
     *
     * @return array
     */
    public function syncLaterItems($unreadItemsIds, $tagsIds, $limit = 100)
    {
        if ($limit < 1 || !count($tagsIds)) {
            return [];
        }
        $totalUnread = $this->laterItemRepository->totalUnreadLabelsItems($tagsIds);
        $items = $this->getUnreadItemsIdsRecursive($tagsIds, $unreadItemsIds, 0, $limit + 5, $totalUnread); //"+5" extra to don't do many loops for few items

        return SavedItemTransformer::transformList($items);
    }

    /**
     * Get unread later items recursively
     *
     * @param array $labelsIds array of labels ids from which sync items
     * @param array $unreadIds still unread items ids from api
     * @param int $begin position from which begin limit in query
     * @param int $limit limit of items for query
     * @param int $total total unread items in data base
     *
     * @return array
     */
    private function getUnreadItemsIdsRecursive(array $labelsIds, array $unreadIds, $begin, $limit, $total)
    {
        $unreadItems = $this->laterItemRepository->getUnreadForApiByLabels($labelsIds, $begin, $limit);
        $relatedItemsTags = $this->laterItemRepository->getTagsByUserItemIds(ArrayHelper::getIdsFromArray($unreadItems, 'ui_id'));
        $unreadItems = SavedItemTransformer::addItemsTags($unreadItems, ArrayHelper::joinValuesSameKey($relatedItemsTags, 'ui_id', 'tag_id'));
        if (!count($unreadIds)) {
            return $unreadItems;
        }

        $unreadItems = ArrayHelper::filterUnreadItemsIds($unreadItems, $unreadIds, 'ui_id');
        $unreadCount = count($unreadItems);
        $begin += $limit;

        if ($unreadCount >= $limit || ($begin + 1) >= $total || $limit < 5) { //added 5 just in case to don't do a lot of loops for few items
            return $unreadItems;
        }

        $limit -= $unreadCount;
        if (($begin + $limit) > $total) {
            $limit = $total - $begin;
        }
        $moreUnreadItems = $this->getUnreadItemsIdsRecursive($labelsIds, $unreadIds, $begin, $limit, $total);
        $unreadItems     = array_merge($unreadItems, $moreUnreadItems);

        return $unreadItems;
    }

    /**
     * Add read items which came as unread from api
     *
     * @param array $tagItems
     * @param array $readItems
     *
     * @return array
     */
    private function addReadItems($tagItems, $readItems)
    {
        foreach ($readItems as $readItem) {
            $item       = [
                'article_id'    => $readItem['article_id'],
                'feed_id'   => 0,
                'tags'    => [],
                'is_unread' => false,
                'date_add'  => 0,
                'language'  => "",
                'link'      => "",
                'title'     => "",
                'content'   => ""
            ];
            $tagItems[] = $item;
        }

        return $tagItems;
    }

    /**
     * Sync tags items relation
     *
     * @param User  $user
     * @param array $tagItems selected items to be read later
     *
     * @return array
     */
    public function syncLaterItemsApi(User $user, array $tagItems)
    {
        if (!count($tagItems)) {
            return [];
        }

        $this->syncSavedItemsTags($tagItems);
        //get complete content for partial articles
        $this->queueLauncher->executeCrawling($user->getId());

        return $this->getTagsItemsRelationByUIs(ArrayHelper::getIdsFromArray($tagItems, 'article_id'));
    }

    /**
     * Sync shared item from api
     *
     * @param User  $user
     * @param array $sharedItems
     *
     * @return array
     */
    public function syncShared(User $user, array $sharedItems)
    {
        if (!count($sharedItems)) {
            return;
        }

        $this->addSharedItems($user, $sharedItems);
    }

    /**
     * Add shared pages from api
     *
     * @param User  $user
     * @param array $sharedItems
     */
    public function addSharedItems(User $user, $sharedItems)
    {
        foreach ($sharedItems as $sharedItem) {
            $this->laterItem->addPageToLater($user, $sharedItem['tag_id'], $sharedItem['title'], $sharedItem['text'], true);
        }
    }

    /**
     * Sync later item to API and update the reviewed items
     *
     * @param User  $user
     * @param array $dictateItems items for dictation
     * @param int   $limit        limit of dictations to sync
     *
     * @return array
     */
    public function syncDictateItems(User $user, $dictateItems, $limit)
    {
        $error  = false;
        $result = array();
        list($unreadItems, $readItems) = ArrayHelper::separateBooleanArray($dictateItems, 'is_unread');
        if (empty($error) && is_array($readItems) && count($readItems)) {
            $this->laterItemRepository->syncViewedLaterItems($readItems);
        }
        if (empty($error)) {
            $result = $this->laterItem->getUnreadItemsApi($user->getPreference()->getDictationTagId(), $unreadItems, $limit);
        }
        $responseData = array(
            'error'  => $error,
            'result' => $result,
        );

        return $responseData;
    }

    /**
     * @param array $apiItem
     * @param array $dbItemTags
     *
     * With this logic could be a problem if from two devices are selected different tags to same articles.
     * We be added only from last sync device.
     */
    protected function checkItemTagsDifferences(array $apiItem, array $dbItemTags)
    {
        $uiId = $apiItem['article_id'];
        $apiItemTags = $apiItem['tags'];
        $dbTags = [];
        foreach ($dbItemTags as $dbItemTag) {
            if (!in_array($dbItemTag['tag_id'], $apiItemTags, false)) {
                $this->itamTageRemove[] = $dbItemTag['id'];
            }
            $dbTags[] = $dbItemTag['tag_id'];
        }

        foreach ($apiItemTags as $apiItemTag) {
            if (!in_array($apiItemTag, $dbTags, false)) {
                $this->itamTageAdd[] = [
                    'tag_id' => $apiItemTag,
                    'ui_id' => $uiId
                ];
            }
        }
    }

    /**
     * @param array $userItems
     *
     * @return array
     */
    protected function getTagsItemsRelationByUIs(array $userItems)
    {
        return SavedItemTransformer::transformListRelation($this->laterItemRepository->getTagsByUserItemIds($userItems));
    }

    /**
     * @param array $items
     */
    protected function syncSavedItemsTags(array $items)
    {
        if (!count($items)) {
            return;
        }

        $this->itamTageRemove = [];
        $this->itamTageAdd = [];
        $apiItems = ArrayHelper::moveContendUnderKey($items, 'article_id');
        $dbItems = $this->laterItemRepository->getTagsByUserItemIds(ArrayHelper::getIdsFromArray($items, 'article_id'));
        $dbItems = ArrayHelper::moveContendUnderRepetitiveKey($dbItems, 'ui_id');


        foreach ($dbItems as $uiId => $dbItem) {
            $apiItemTags = $apiItems[$uiId]['tags'];
            if (count($apiItemTags)) {
                $this->checkItemTagsDifferences($apiItems[$uiId], $dbItem);
            }
        }

        if (count($this->itamTageRemove)) {
            $this->laterItemRepository->markAsRead($this->itamTageRemove);
            $this->itamTageRemove = [];
        }

        if (count($this->itamTageAdd)) {
            $this->addItemsTagsRelation();
        }
    }

    protected function addItemsTagsRelation()
    {
        $savedArticles = $this->laterItemRepository->getTagsByUserItemIds(array_unique(ArrayHelper::getIdsFromArray($this->itamTageAdd, 'ui_id')), false);
        $markAsRead = [];
        foreach ($savedArticles as $savedArticle) {
            foreach ($this->itamTageAdd as $itemKey => $itemTag) {
                if ($savedArticle['ui_id'] == $itemTag['ui_id'] && $savedArticle['tag_id'] == $itemTag['tag_id']) {
                    $markAsRead[] = $savedArticle;
                    unset($this->itamTageAdd[$itemKey]);
                }
            }
        }

        $this->laterItemRepository->markAsRead(ArrayHelper::getIdsFromArray($markAsRead), true);
        $this->laterItemRepository->insertTagItems($this->itamTageAdd);
        $this->itamTageRemove = [];
    }
}
