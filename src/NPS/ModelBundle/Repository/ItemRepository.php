<?php

namespace NPS\ModelBundle\Repository;

use NPS\ModelBundle\Entity\Item;
use NPS\ModelBundle\Entity\User;
use NPS\ModelBundle\Entity\UserFeed;
use NPS\ModelBundle\Entity\UserItem;
use NPS\ModelBundle\Repository\BaseRepository;

/**
 * ItemRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ItemRepository extends BaseRepository
{
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
        $em = $this->getEntityManager();
        $item = new Item();
        $item->setFeed($feed);
        $item->setTitle($itemData->get_title());
        $item->setLink($itemData->get_link());
        $item->setContent($itemData->get_description());
        $item->setContentHash(sha1($itemData->get_description()));
        $item->setDateAdd($itemData->get_date('U'));
        $em->persist($item);
        $em->flush();

        $this->addItemToSubscribers($item, $feed->getUserFeeds());
    }

    /**
     * Add item to subscribers
     * @param Item  $item
     * @param array $userFeeds
     */
    private function addItemToSubscribers($item, $userFeeds)
    {
        parent::preExecute();
        foreach ($userFeeds as $userFeed) {
            $userItem = new UserItem();
            $userItem->setUser($userFeed->getUser());
            $userItem->setItem($item);
            $userItem->setIsUnread(true);
            $this->em->persist($userItem);
        }
        $this->em->flush();
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

    /**
     * Change item status
     * @param User $user
     * @param Item $item
     * @param $statusSet
     *
     * @param null $change
     */
    public function changeStatus(User $user, Item $item, $statusSet, $change = null)
    {
        $statusGet = 'get'.$statusSet;
        $statusSet = 'set'.$statusSet;
        $em = $this->getEntityManager();
        $userItem = $this->hasItem($user->getId(), $item->getId());

        if ($userItem instanceof UserItem) {
            if ($change == 1) {
                $status = true;
            } elseif ($change == 2)  {
                $status = false;
            } else {
                if ($userItem->$statusGet()) { //change actual status
                    $status = false;
                } else {
                    $status = true;
                }
            }
        } else {
            $userItem = new UserItem();
            $userItem->setUser($user);
            $userItem->setItem($item);
            if ($change == 1) {
                $status = true;
            } else {
                $status = false;
            }
        }
        $userItem->$statusSet($status);
        $em->persist($userItem);
        $em->flush();
    }

    /**
     * Check if are relation between user and item.
     * @param $userId
     * @param $itemId
     *
     * @return null|Item
     */
    public function hasItem($userId, $itemId)
    {
        parent::preExecute();
        $repository = $this->em->getRepository('NPSModelBundle:UserItem');
        $query = $repository->createQueryBuilder('o')
            ->where('o.user = :userId')
            ->andWhere('o.item = :itemId')
            ->setParameter('userId', $userId)
            ->setParameter('itemId', $itemId)
            ->getQuery();
        $itemCollection = $query->getResult();
        $item = null;

        if (count($itemCollection) == 1) {
            foreach ($itemCollection as $value) {
                $item = $value;
            }
        }

        return $item;
    }

    /**
     * Check if user can see this item
     * @param $userId
     * @param $itemId
     *
     * @return bool
     */
    public function canSee($userId, $itemId)
    {
        $em = $this->getEntityManager();
        $repository = $em->getRepository('NPSModelBundle:Feed');
        $query = $repository->createQueryBuilder('f')
            ->join('f.userFeeds', 'uf')
            ->join('f.items', 'e')
            ->join('uf.user', 'u')
            ->where('u.id = :userId')
            ->andWhere('e.id = :itemId')
            ->setParameter('userId', $userId)
            ->setParameter('itemId', $itemId)
            ->getQuery();
        $feedCollection = $query->getResult();
        if (count($feedCollection)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param integer $userId
     * @param array   $items
     */
    public function syncViewedItems($userId, $items)
    {
        parent::preExecute();
        foreach ($items as $itemData) {
            $userItem = $this->hasItem($userId, $itemData['id']);
            $userItem->setIsUnread($itemData['is_unread']);
            $userItem->setIsStared($itemData['is_stared']);
            $this->em->persist($userItem);
        }
        $this->em->flush();
    }

    /**
     * Get users unread items
     * @param $userId
     * @return mixed
     */
    public function getUnreadItemsApi($userId)
    {
        parent::preExecute();
        $repository = $this->em->getRepository('NPSModelBundle:Item');
        $query = $repository->createQueryBuilder('i')
            ->select('i.id, i.title, i.link, i.content, ui.isStared, i.dateAdd')
            ->leftJoin('i.userItems', 'ui')
            ->where('ui.isUnread = :isUnread')
            ->andWhere('ui.user = :userId')
            ->setParameter('isUnread', true)
            ->setParameter('userId', $userId)
            ->getQuery();
        $itemCollection = $query->getResult();

        return $itemCollection;
    }
}