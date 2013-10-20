<?php
namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bridge\Monolog\Logger;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\FrontendBundle\Services\SystemNotificationService;

/**
 * AbstractEntityService
 */
abstract class AbstractEntityService
{
    /**
     * @var $doctrine Registry
     */
    protected  $doctrine;

    /**
     * @var $entityManager Entity Manager
     */
    protected $entityManager;

    /**
     * @var $doctrine Registry
     */
    protected $logger;

    /**
     * @var $doctrine Registry
     */
    protected $systemNotification;


    /**
     * @param Registry                  $doctrine           Registry
     * @param Logger                    $logger             Logger
     * @param SystemNotificationService $systemNotification SystemNotificationService
     */
    public function __construct(Registry $doctrine, Logger $logger, SystemNotificationService $systemNotification)
    {
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->logger = $logger;
        $this->systemNotification = $systemNotification;
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
