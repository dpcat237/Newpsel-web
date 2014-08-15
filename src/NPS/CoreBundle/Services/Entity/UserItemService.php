<?php
namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\CoreBundle\Constant\EntityConstants;
use NPS\CoreBundle\Entity\Item;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Entity\UserItem;

/**
 * UserItemService
 */
class UserItemService extends AbstractEntityService
{
    /**
     * Change item status
     *
     * @param User   $user
     * @param Item   $item
     * @param string $statusGet
     * @param string $statusSet
     * @param null   $change
     *
     * @return boolean set status
     */
    public function changeStatus(User $user, Item $item, $statusGet, $statusSet, $change = null)
    {
        $userItemRepo = $this->doctrine->getRepository('NPSCoreBundle:UserItem');
        $userItem = $userItemRepo->hasItem($user->getId(), $item->getId());

        if ($userItem instanceof UserItem) {
            $status = $this->changeUserItemStatus($userItem, $statusGet, $statusSet, $change);

            return $status;
        }

        $userItem = new UserItem();
        $userItem->setUser($user);
        $userItem->setItem($item);
        if ($change == 1) {
            $status = true;
        } else {
            $status = false;
        }

        $userItem->$statusSet($status);
        $this->entityManager->persist($userItem);
        $this->entityManager->flush();

        return $status;
    }

    /**
     * Set new user's item status
     *
     * @param UserItem $userItem  user's item
     * @param string   $statusGet status get method name
     * @param string   $statusSet status set method name
     * @param int      $change    new status
     *
     * @return bool
     */
    public function changeUserItemStatus(UserItem $userItem, $statusGet, $statusSet, $change = null)
    {
        $status = $this->getNewStatus($userItem, $change, $statusGet);
        $userItem->$statusSet($status);
        $this->entityManager->persist($userItem);
        $this->entityManager->flush();

        return $status;
    }

    /**
     * Get new status for existing userItem
     *
     * @param UserItem $userItem
     * @param int      $change
     * @param string   $statusGet
     *
     * @return bool
     */
    private function getNewStatus(UserItem $userItem, $change, $statusGet)
    {
        if ($change == EntityConstants::STATUS_UNREAD) {
            $status = true;
        } elseif ($change == EntityConstants::STATUS_READ)  {
            $status = false;
        } else {
            if ($userItem->$statusGet()) { //change current status
                $status = false;
            } else {
                $status = true;
            }
        }

        return $status;
    }
}