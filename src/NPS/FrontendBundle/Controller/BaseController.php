<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use NPS\CoreBundle\Controller\CoreController;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * BaseController
 */
abstract class BaseController extends CoreController
{
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
            $objectRepo = $this->em->getRepository('NPSCoreBundle:'.$objectName);
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
        $objectRepo = $this->em->getRepository('NPSCoreBundle:'.$objectName);
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
     * Save generic form
     * @param string $objectName [description]
     * @param object $form       [description]
     */
    protected function saveGenericForm($objectName, $form, $msg = null)
    {
        $this->createNotification($objectName);
        $formObject = $form->getData();
        $msg = ($msg)? $msg : 101;

        if ($form->isValid()) {
            $this->saveObject($formObject);
            $this->notification->setNotification($msg);
        } else {
            $this->notification->setNotification(201);
        }
        $this->setNotificationMessage();
    }
}