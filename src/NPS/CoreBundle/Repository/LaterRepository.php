<?php

namespace NPS\CoreBundle\Repository;

use NPS\CoreBundle\Entity\Later;
use NPS\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * LaterRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class LaterRepository extends EntityRepository
{
    /**
     * Create label
     *
     * @param User    $user      User
     * @param string  $labelName label name
     * @param boolean $isBasic   set if label is basic
     *
     * @return Later | null
     */
    public function createLabel(User $user, $labelName, $isBasic = false)
    {
        $entityManager = $this->getEntityManager();
        if (!$this->hasLabelByName($user->getId(), $labelName)) {
            $label = new Later();
            $label->setName($labelName);
            $label->setUser($user);
            $label->setBasic($isBasic);

            $entityManager->persist($label);
            $entityManager->flush();

            return $label;
        }

        return null;
    }

    /**
     * Check if user already has this label
     * @param $userId
     * @param $labelName
     *
     * @return bool
     */
    public function hasLabelByName($userId, $labelName)
    {
        $query = $this->createQueryBuilder('l')
            ->where('l.user = :userId')
            ->andWhere('l.name = :name')
            ->setParameter('userId', $userId)
            ->setParameter('name', $labelName)
            ->getQuery();
        $collection = $query->getResult();

        if (count($collection)) {
            foreach ($collection as $label) {
                return $label;
            }
        }

        return false;
    }

    /**
     * Get list of labels of user
     * @param $userId
     *
     * @return mixed
     */
    public function getUserLabel($userId)
    {
        $query = $this->createQueryBuilder('l')
            ->where('l.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('l.name', 'ASC')
            ->getQuery();

        return $query->getResult();
    }

    /**
     * Get labels of user with count of later items
     *
     * @param integer $userId
     * @param boolean $all
     *
     * @return array
     */
    public function getLabelsForMenu($userId, $all = false)
    {
        $laterItemRepository = $this->getEntityManager()->getRepository('NPSCoreBundle:LaterItem');
        $query = $laterItemRepository->createQueryBuilder('li')
            ->select('l.id, l.name, COUNT(li.id) total')
            ->innerJoin('li.later', 'l')
            ->where('l.user = :userId')
            ->andWhere('l.enabled = :enabled')
            ->groupBy('l.id')
            ->orderBy('l.name', 'ASC')
            ->setParameter('userId', $userId)
            ->setParameter('enabled', true);
        if (!$all) {
            $query
                ->andWhere('li.unread = :unread')
                ->setParameter('unread', true);
        }
        $query = $query->getQuery();

        return $query->getArrayResult();
    }

    /**
     * Get query of list of labels of user
     * @param $userId
     *
     * @return string
     */
    public function getUserLabelsQuery($userId)
    {
        $query = $this->createQueryBuilder('l')
            ->where('l.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('l.name', 'ASC');

        return $query;
    }

    /**
     * Get user's labels list for api
     *
     * @param int $userId
     *
     * @return array
     */
    public function getUserLabelsApi($userId)
    {
        $query = $this->createQueryBuilder('l')
            ->select('l.id AS tag_id, l.name, l.dateUp AS date_up')
            ->where('l.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery();

        return $query->getArrayResult();
    }

    /**
     * Get user's labels list for api
     *
     * @param int   $userId
     * @param int   $lastUpdate
     * @param array $changedLabels
     *
     * @return array
     */
    public function getUserLabelsApiCreated($userId, $lastUpdate = 0, $changedLabels)
    {
        $changedIds = array();
        foreach ($changedLabels as $changedLabel) {
            $changedIds[] = $changedLabel['id'];
        }
        $changedIds = implode(',', $changedIds);

        $query = $this->createQueryBuilder('l')
            ->select('l.id AS api_id, l.name, l.dateUp AS lastUpdate')
            ->where('l.user = :userId')
            ->andWhere('l.dateUp > :lastUpdate')
            ->andWhere('l.id NOT IN (:changedIds)')
            ->orderBy('l.dateUp', 'ASC')
            ->setParameter('userId', $userId)
            ->setParameter('lastUpdate', $lastUpdate)
            ->setParameter('changedIds', $changedIds)
            ->getQuery();
        $collection = $query->getResult();

        return $collection;
    }

    /**
     * @param array $tagsIds
     */
    public function removeTags($tagsIds)
    {
        $query = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.id IN (:tagsTds)')
            ->setParameter('tagsTds', $tagsIds)
            ->getQuery();
        $query->execute();
    }

    /**
     * Update label data
     *
     * @param int    $labelId
     * @param string $name
     * @param int    $dateUp
     */
    public function updateLabel($labelId, $name, $dateUp)
    {
        $query = $this->createQueryBuilder('l')
            ->update()
            ->set('l.name', ':name')
            ->set('l.dateUp', ':dateUp')
            ->where('l.id = :labelId')
            ->setParameter('labelId', $labelId)
            ->setParameter('name', $name)
            ->setParameter('dateUp', $dateUp)
            ->getQuery();
        $query->execute();
    }
}
