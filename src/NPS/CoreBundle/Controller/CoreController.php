<?php

namespace NPS\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * CoreController
 */
abstract class CoreController extends Controller
{
    //we will have an EntityManager here
    protected $em;

    //we will have the routing here
    protected $router;

    /**
     * This function will be executed before any controller action
     */
    public function preExecute()
    {
        $this->em = $this->getDoctrine()->getManager();
        $this->router = $this->container->get('router');
    }

    /**
     * Check that route exists
     * @param string $routeName
     *
     * @return bool
     */
    protected function checkRoute($routeName)
    {
        try {
            $this->router->generate($routeName);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Save object to data base
     * @param string $formObject [description]
     */
    protected function saveObject($formObject)
    {
        try {
            $this->em->persist($formObject);
            $this->em->flush();
            $this->get('system_notification')->setMessage(NotificationHelper::SAVED_OK);
        } catch (\Exception $e) {
            $this->get('logger')->err(__METHOD__.' '.$e->getMessage());
            $this->get('system_notification')->setMessage(NotificationHelper::ERROR_TRY_AGAIN);
        }
    }

    /**
     * Generic change object status (enabled / disabled)
     * @param string  $objectName  [description]
     * @param string  $objectClass [description]
     * @param integer $id          [description]
     * @param string  $func        [description]
     *
     * @return boolean
     */
    protected function genericChangeObjectStatus($objectName, $objectClass, $id, $func = 'IsEnabled')
    {
        $objectRepo = $this->em->getRepository('NPSCoreBundle:'.$objectName);
        $object = $objectRepo->find($id);
        $funcGet = 'get'.$func;
        $funcSet = 'set'.$func;

        if ($object instanceof $objectClass) {
            try {
                if ($object->$funcGet()) {
                    $object->$funcSet(0);
                } else {
                    $object->$funcSet(1);
                }

                $this->em->persist($object);
                $this->em->flush();
            } catch (\Exception $e) {
                $this->get('logger')->err(__METHOD__.' '.$e->getMessage());
            }

            $check = true;
        } else {
            $check = false;
        }

        return $check;
    }
}