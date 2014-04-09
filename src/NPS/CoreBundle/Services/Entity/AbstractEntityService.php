<?php
namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bridge\Monolog\Logger;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\FrontendBundle\Services\SystemNotificationService,
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
     * @var Logger
     */
    protected $logger;

    /**
     * @var SystemNotificationService
     */
    protected $systemNotification;

    /**
     * @var UserWrapper
     */
    protected $userWrapper;


    /**
     * @param Registry                  $doctrine           Registry
     * @param Logger                    $logger             Logger
     * @param SystemNotificationService $systemNotification SystemNotificationService
     * @param UserWrapper               $userWrapper        UserWrapper
     */
    public function __construct(Registry $doctrine, Logger $logger, SystemNotificationService $systemNotification, UserWrapper $userWrapper)
    {
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->logger = $logger;
        $this->systemNotification = $systemNotification;
        $this->userWrapper = $userWrapper;
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
                $this->systemNotification->setMessage(NotificationHelper::SAVED_OK);
            }
        } catch (\Exception $e) {
            $this->logger->err(__METHOD__.' '.$e->getMessage());
            if ($notify) {
                $this->systemNotification->setMessage(NotificationHelper::ERROR_TRY_AGAIN);
            }
        }
    }
}
