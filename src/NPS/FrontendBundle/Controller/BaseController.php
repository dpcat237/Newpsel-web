<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * BaseController
 */
abstract class BaseController extends Controller
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
     * Create form depends if it's for edit or create
     * @param integer $objectId        [description]
     * @param string  $objectName      [description]
     * @param class   $objectClass     [description]
     * @param class   $objectTypeClass [description]
     * @param array   $formSets        [description]
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function createFormEdit($objectId, $objectName, $objectClass, $objectTypeClass, $formSets = null)
    {
        $object = null;
        $objectName = str_replace(' ', '', $objectName);

        if ($objectId) {
            $objectRepo = $this->em->getRepository('NPSModelBundle:'.$objectName);
            $object = $objectRepo->find($objectId);

            if ($object instanceof $objectClass) {
                $formType = (count($formSets))? $this->formSets(new $objectTypeClass($object), $formSets): new $objectTypeClass($object);
                $form = $this->createForm($formType, $object);
            }
        }

        if (!$object instanceof $objectClass) {
            $formType = (count($formSets))? $this->formSets(new $objectTypeClass($object), $formSets): new $objectTypeClass(new $objectClass());
            $form = $this->createForm($formType);
        }

        return $form;
    }

    /**
     * Set additional values to form type
     * @param class $formType [description]
     * @param array $formSets [description]
     *
     * @return class $formType
     */
    private function formSets($formType, $formSets)
    {
        foreach ($formSets as $formSet => $value) {
            $formType->$formSet($value);
        }

        return $formType;
    }

    /**
     * Create frontend response for edit form
     * @param string $objectName    [description]
     * @param string $routeName     [description]
     * @param object $routeNameMany [description]
     * @param object $form          [description]
     * @param string $template      [description]
     *
     * @return Render
     */
    protected function createFormResponse($objectName, $routeName, $routeNameMany, $form, $template = 'edit')
    {
        $name = $objectName;
        $objectName = str_replace(' ', '', $objectName);
        //render template or redirect to edit page
        $notification = new NotificationHelper($objectName);

        if (is_object($notification) && $notification->getMessageType() == 'success') {
            return new RedirectResponse($this->router->generate($routeName.'_'.$template, array('id' => $this->formObject->getId())));
        } else {
            return $this->render('NPSFrontendBundle:'.$objectName.':'.$template.'.html.twig', array(
                'heading' => 'Manage '.$name,
                'form' => $form->createView(),
                'url_create' => $this->router->generate($routeName.'_edit'),
                'url_list' => $this->router->generate($routeNameMany)
            ));
        }
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
    protected function genericListRender($objectName, $routeName, $routeNameMany, $orderBy = array(), $where = array())
    {
        $name = $objectName;
        $objectName = str_replace(' ', '', $objectName);
        $objectRepo = $this->em->getRepository('NPSModelBundle:'.$objectName);
        $objectCollection = $objectRepo->getListPagination(0, 0, $orderBy, $where);

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
     * Generate generic list of objects
     * @param string  $objectName    [description]
     * @param string  $routeName     [description]
     * @param string  $routeNameMany [description]
     * @param integer $pageActual    [description]
     * @param array   $orderBy       [description]
     * @param array   $where         [description]
     *
     * @return object render
     */
    protected function genericListRenderPages($objectName, $routeName, $routeNameMany, $pageActual, $orderBy = array(), $where = array())
    {
        $name = $objectName;
        $objectName = str_replace(' ', '', $objectName);
        $objectRepo = $this->em->getRepository('NPSModelBundle:'.$objectName);
        $page = new PaginationHelper($pageActual, $objectRepo->getCount(), 10);
        $objectCollection = $objectRepo->getListPagination($page->getRegistersOffset(), $page->getRegistersLimit(), $orderBy, $where);

        $renderData = array(
            'heading' => $this->get('translator')->trans($name),
            'url_list' => $this->router->generate($routeNameMany),
            $routeNameMany => $objectCollection,
            'page_total' => $page->getPageTotal(),
            'page_previous' => $page->getPagePrevious(),
            'page_actual' => $page->getPageActual(),
            'page_next' => $page->getPageNext()
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
     * Save generic form
     * @param string $objectName [description]
     * @param object $form       [description]
     */
    protected function saveGenericForm($objectName, $form)
    {
        $this->createNotification($objectName);
        $formObject = $form->getData();

        if ($form->isValid()) {
            $this->saveObject($formObject);
        } else {
            $this->notification->setNotification(201);
        }
        $this->setNotificationMessage();
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