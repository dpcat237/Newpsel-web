<?php
namespace NPS\CoreBundle\Services\Entity;

use NPS\CoreBundle\Entity\LaterItem;
use NPS\CoreBundle\Services\Entity\ItemService;

/**
 * LaterItemService
 */
class LaterItemService
{
    /**
     * @var $cache Redis
     */
    private $cache;

    /**
     * @var $doctrine Doctrine
     */
    private $doctrine;

    /**
     * @var $entityManager Entity Manager
     */
    private $entityManager;

    /**
     * @var $entityManager ItemService
     */
    private $item;


    /**
     * @param Doctrine     $doctrine Doctrine
     * @param CacheService $cache    Redis service
     * @param ItemService  $item     Item service
     */
    public function __construct($doctrine, $cache, ItemService $item)
    {
        $this->cache = $cache;
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->item = $item;
    }

    /**
     * Get Item and make read laterItem and Item. If are get complete content from cache
     *
     * @param LaterItem $laterItem
     *
     * @return \NPS\CoreBundle\Entity\Item
     */
    public function readItem(LaterItem $laterItem)
    {
        $item = $laterItem->getUserItem()->getItem();
        $user = $laterItem->getUserItem()->getUser();
        $this->makeLaterRead($laterItem);
        $this->item->changeStatus($user, $item, "isUnread", "setIsUnread", 2);

        if ($content = $this->cache->get('crawledItem_'.$item->getId())) {
            $item->setContent($content);
        }

        return $item;
    }

    /**
     * Make later item read
     * @param LaterItem $laterItem
     */
    public function makeLaterRead(LaterItem $laterItem)
    {
        $laterItem->setIsUnread(false);
        $this->entityManager->persist($laterItem);
        $this->entityManager->flush();
    }
}