<?php

namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Exception;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Services\NotificationManager,
    NPS\CoreBundle\Services\UserWrapper;

/**
 * Class AbstractEntityService
 *
 * @package NPS\CoreBundle\Services\Entity
 */
abstract class AbstractEntityService
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var NotificationManager */
    protected $notification;

    /**
     * AbstractEntityService constructor.
     *
     * @param EntityManager       $entityManager
     * @param UserWrapper         $userWrapper
     * @param NotificationManager $notification
     */
    public function __construct(EntityManager $entityManager, UserWrapper $userWrapper, NotificationManager $notification)
    {
        $this->entityManager = $entityManager;
        $this->userWrapper   = $userWrapper;
        $this->notification  = $notification;
        $this->setRepository();
    }

    /**
     * Remove object function
     *
     * @param $object
     */
    public function removeObject($object)
    {
        $this->entityManager->remove($object);
        $this->entityManager->flush();
    }

    /**
     * Save object to data base
     *
     * @param      $object
     * @param bool $notify
     */
    public function saveObject($object, $notify = false)
    {
        try {
            $this->entityManager->persist($object);
            $this->entityManager->flush();
            if ($notify) {
                $this->notification->setFlashMessage(NotificationHelper::SAVED_OK);
            }
        } catch (Exception $e) {
            $this->notification->setLog(__METHOD__ . ' ' . $e->getMessage(), 'err');
            if ($notify) {
                $this->notification->setFlashMessage(NotificationHelper::ERROR_TRY_AGAIN);
            }
        }
    }

    /**
     * Set own repository
     *
     * @return EntityRepository
     */
    abstract protected function setRepository();
}
