<?php
namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Services\NotificationManager,
    NPS\CoreBundle\Services\UserWrapper;

/**
 * AbstractEntityService
 */
abstract class AbstractEntityService
{
    /**
     * @var Registry
     */
    protected  $doctrine;

    /**
     * @var Entity Manager
     */
    protected $entityManager;

    /**
     * @var NotificationManager
     */
    protected $notification;


    /**
     * @param Registry            $doctrine     Registry
     * @param UserWrapper         $userWrapper  UserWrapper
     * @param NotificationManager $notification NotificationManager
     */
    public function __construct(Registry $doctrine, UserWrapper $userWrapper, NotificationManager $notification)
    {
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->userWrapper = $userWrapper;
        $this->notification = $notification;
    }

    /**
     * Remove object function
     * @param $object
     */
    public function removeObject($object)
    {
        $this->entityManager->remove($object);
        $this->entityManager->flush();
    }

    /**
     * Save object to data base
     * @param $object
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
        } catch (\Exception $e) {
            $this->notification->setLog(__METHOD__.' '.$e->getMessage(), 'err');
            if ($notify) {
                $this->notification->setFlashMessage(NotificationHelper::ERROR_TRY_AGAIN);
            }
        }
    }
}
