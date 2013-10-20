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
    /**
     * Create form depends if it's for edit or create
     * @param integer $objectId        [description]
     * @param string  $objectName      [description]
     * @param class   $objectClass     [description]
     * @param class   $objectTypeClass [description]
     * @param array   $formSets        [description]
     *
     * @return Form
     */
    protected function createFormEdit($objectId, $objectName, $objectClass, $objectTypeClass, $formSets = null)
    {
        $object = null;
        $objectName = str_replace(' ', '', $objectName);

        if ($objectId) {
            $form = $this->createFormExistObject($objectId, $objectName, $objectClass, $objectTypeClass, $formSets);
        }

        if (!$object instanceof $objectClass) {
            $formType = (count($formSets))? $this->formSets(new $objectTypeClass($object), $formSets): new $objectTypeClass(new $objectClass());
            $form = $this->createForm($formType);
        }

        return $form;
    }

    /**
     * Get Form of exist object
     * @param $objectId
     * @param $objectName
     * @param $objectClass
     * @param $objectTypeClass
     * @param null $formSets
     *
     * @return null|\Symfony\Component\Form\Form
     */
    private function createFormExistObject($objectId, $objectName, $objectClass, $objectTypeClass, $formSets = null)
    {
        $objectRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:'.$objectName);
        $object = $objectRepo->find($objectId);

        if ($object instanceof $objectClass) {
            $formType = (count($formSets))? $this->formSets(new $objectTypeClass($object), $formSets): new $objectTypeClass($object);

            return $this->createForm($formType, $object);
        }

        return null;
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

        if (is_object($notification) && $notification->getMessageType() == 'success') { //TODO: Refactory for new notification service
            $route = $this->container->get('router')->generate($routeName.'_'.$template, array('id' => $this->formObject->getId()));

            return new RedirectResponse($route);
        } else {
            $routeEdit = $this->container->get('router')->generate($routeName.'_edit');
            $routeMany = $this->container->get('router')->generate($routeNameMany);

            return $this->render('NPSFrontendBundle:'.$objectName.':'.$template.'.html.twig', array(
                'heading' => 'Manage '.$name,
                'form' => $form->createView(),
                'url_create' => $routeEdit,
                'url_list' => $routeMany
            ));
        }
    }
}