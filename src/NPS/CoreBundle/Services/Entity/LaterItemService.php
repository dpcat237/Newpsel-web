<?php
namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\CoreBundle\Entity\LaterItem;
use NPS\CoreBundle\Services\CacheService,
    NPS\CoreBundle\Services\Entity\ItemService;

/**
 * LaterItemService
 */
class LaterItemService
{
    /**
     * @var Redis
     */
    private $cache;

    /**
     * @var Doctrine
     */
    private $doctrine;

    /**
     * @var Entity Manager
     */
    private $entityManager;

    /**
     * @var ItemService
     */
    private $item;


    /**
     * @param Registry     $doctrine Doctrine Registry
     * @param CacheService $cache    CacheService
     * @param ItemService  $item     Item service
     */
    public function __construct(Registry $doctrine, CacheService $cache, ItemService $item)
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
        $this->item->changeStatus($user, $item, "isUnread", "setUnread", 2);

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
        $laterItem->setUnread(false);
        $this->entityManager->persist($laterItem);
        $this->entityManager->flush();
    }
}