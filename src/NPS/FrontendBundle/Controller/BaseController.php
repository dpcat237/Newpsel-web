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
     * @return Form
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

        if (is_object($notification) && $notification->getMessageType() == 'success') { //TODO: Refactory for new notification service
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
     * Save generic form
     * @param Form $form form object
     */
    protected function saveGenericForm($form)
    {
        $formObject = $form->getData();

        if ($form->isValid()) {
            $this->saveObject($formObject);
        } else {
            $this->get('system_notification')->setMessage(NotificationHelper::ALERT_FORM_DATA);
        }
    }
}