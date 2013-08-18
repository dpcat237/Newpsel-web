<?php
namespace NPS\CoreBundle\Services;

use HTMLPurifier;
use HTMLPurifier_Config;
use NPS\CoreBundle\Entity\Item;
use NPS\CoreBundle\Entity\UserItem;

/**
 * ItemService
 */
class ItemService
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
     * @var $purifier HTMLPurifier
     */
    private $purifier;

    /**
     * @param Doctrine     $doctrine
     * @param CacheService $cache
     */
    public function __construct($doctrine, $cache)
    {
        $this->cache = $cache;
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();

        if (empty($this->purifier)) {
            $config = HTMLPurifier_Config::createDefault();
            $this->purifier = new HTMLPurifier($config);
        }
    }

    /**
     * Add item
     * @param object $itemData
     * @param Feed   $feed
     */
    public function addItem($itemData, $feed)
    {
        //$author = $this->getOneMany($itemData->get_author(), $itemData->get_authors());
        //$category = $this->getOneMany($itemData->get_category(), $itemData->get_categories());
        //TODO: add author, category

        $item = $this->checkExistByLink($itemData->get_link());
        if ($item instanceof Item) {
            $item->setTitle($itemData->get_title());
            $item->setContent($itemData->get_description());

            $this->entityManager->persist($item);
            $this->entityManager->flush();
        } else {
            $item = new Item();
            $item->setFeed($feed);
            $item->setDateAdd($itemData->get_date('U'));
            $item->setContentHash(sha1($itemData->get_description()));
            $item->setLink($itemData->get_link());
            $item->setTitle($this->purifier->purify($itemData->get_title()));
            $item->setContent($this->purifier->purify($itemData->get_description()));

            $this->entityManager->persist($item);
            $this->entityManager->flush();

            $linkHash = "item_url_hash_".sha1($itemData->get_link());
            $ttl = 86400;
            $this->cache->setex($linkHash, $ttl, $item->getId());

            $this->addItemToSubscribers($item, $feed->getUserFeeds());
        }
    }

    /**
     * Check if exist item by url
     * @param $link
     *
     * @return mixed
     */
    public function checkExistByLink($link) {
        $linkHash = "item_url_hash_".sha1($link);
        $itemId = $this->cache->get($linkHash);
        if ($itemId) {
            $itemRepo = $this->doctrine->getRepository('NPSCoreBundle:Item');

            return $itemRepo->find($itemId);
        } else {
            return null;
        }
    }

    /**
     * Add item to subscribers
     * @param Item  $item
     * @param array $userFeeds
     */
    private function addItemToSubscribers($item, $userFeeds)
    {
        foreach ($userFeeds as $userFeed) {
            $userItem = new UserItem();
            $userItem->setUser($userFeed->getUser());
            $userItem->setItem($item);
            $userItem->setIsUnread(true);
            $this->entityManager->persist($userItem);
        }
        $this->entityManager->flush();
    }

    /**
     * Get author
     * @param string $one  [description]
     * @param array  $many [description]
     *
     * @return string
     */
    private function getOneMany($one, $many)
    {
        if ($one && !is_numeric($one)) {
            return $one;
        } elseif (count($many)) {
            $c = 0;
            foreach ($many as $value) {
                if (!$c) {
                    $resultValues = $value;
                }
                $resultValues .= '; '.$value;
                $c++;
            }

            return $resultValues;
        } else {
            return '';
        }
    }
}
