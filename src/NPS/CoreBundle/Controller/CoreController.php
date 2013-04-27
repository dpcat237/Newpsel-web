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

    //notification object
    protected $notification;

    /**
     * This function will be executed before any controller action
     */
    public function preExecute()
    {
        $this->em = $this->getDoctrine()->getManager();
        $this->router = $this->container->get('router');
    }

    /**
     * Generate generic list of objects
     * @param string $objectName    [description]
     * @param string $routeName     [description]
     * @param string $routeNameMany [description]
     * @param array  $orderBy       [description]
     * @param array  $where         [description]
     *
     * @internal param int $pageActual [description]
     * @return object render
     */
    public function genericListRender($objectName, $routeName, $routeNameMany, $orderBy = array(), $where = array())
    {
        $name = $objectName;
        $objectName = str_replace(' ', '', $objectName);
        $objectCollection = $this->getCollection($objectName, $orderBy, $where);

        $renderData = array(
            'heading' => $this->get('translator')->trans($name),
            'url_list' => $this->router->generate($routeNameMany),
            $routeNameMany => $objectCollection,
        );

        if ($this->checkRoute($routeName.'_edit')) {
            $renderData['url_create_list'] = $this->router->generate($routeName.'_edit');
        }

        return $this->render('NPSFrontendBundle:'.$objectName.':list.html.twig', $renderData);
    }

    /**
     * Check that route exists
     * @param string $routeName
     *
     * @return boolean
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
     * Create Notification object
     * @param string $objectName
     */
    protected function createNotification($objectName)
    {
        $this->notification = new NotificationHelper($objectName);
    }

    /**
     * Set template notification message
     */
    protected function setNotificationMessage()
    {
        //set message
        if ($this->notification->getMessageType()) {
            $this->get('session')->getFlashBag()->add($this->notification->getMessageType(), $this->notification->getMessage());
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
            $this->notification->setNotification(1);
        } catch (\Exception $e) {
            $this->get('logger')->err(__METHOD__.' '.$e->getMessage());
            $this->notification->setNotification(301);
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
        $objectRepo = $this->em->getRepository('NPSModelBundle:'.$objectName);
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