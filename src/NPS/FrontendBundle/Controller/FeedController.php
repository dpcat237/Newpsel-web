<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * FeedController
 */
class FeedController extends BaseController
{
    /**
     * List of feeds
     * @param Request $request the current request
     *
     * @return Response
     */
    public function listAction(Request $request)
    {
        /*if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
            return new RedirectResponse($this->router->generate('login'));
        } else {*/
            $objectName = 'Feed';
            $routeName = 'feed';
            $routeNameMany = 'feeds';
            $orderBy = array('title' => 'ASC');

            return $this->genericListRender($objectName, $routeName, $routeNameMany, $orderBy);
        //}
    }

    /**
     * Edit/create form of feeds [GET]
     * Route defined in routing.yml
     * @param Request $request the current request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request)
    {
        /*if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
            return new RedirectResponse($this->router->generate('login'));
        } else {*/
            $objectId = $request->get('id');
            $objectName = 'Feed';
            $routeName = 'feed';
            $routeNameMany = 'feeds';
            $objectClass = 'NPS\ModelBundle\Entity\Feed';
            $form =($objectId)? '\FeedEditType' : '\FeedAddType';
            $objectTypeClass = 'NPS\FrontendBundle\Form\Type'.$form;
            $template =($objectId)? 'edit':'add';

            //depends if it's edit or creation
            $form = $this->createFormEdit($objectId, $objectName, $objectClass, $objectTypeClass);

            return $this->createFormResponse($objectName, $routeName, $routeNameMany, $form, $template);
        //}
    }

    /**
     * Create process form of feeds
     * @param Request $request the current request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addProcess(Request $request)
    {
        //depends if it's edit or creation
        $objectName = 'Feed';
        $routeName = 'feed';
        $routeNameMany = 'feeds';
        $objectClass = 'NPS\ModelBundle\Entity\Feed';
        $objectTypeClass = 'NPS\FrontendBundle\Form\Type\FeedAddType';
        $form = $this->createFormEdit(null, $objectName, $objectClass, $objectTypeClass);
        $form->bind($request);
        $this->createNotification($objectName);
        $formObject = $form->getData();

        if ($form->isValid()) {
            $feedRepo = $this->em->getRepository('NPSModelBundle:Feed');
            $rss = $this->get('fkr_simple_pie.rss');
            $feedRepo->setRss($rss);
            $checkCreate = $feedRepo->createFeed($formObject->getUrl());

            if (!$checkCreate['error']) {
                $checkUpdate = $feedRepo->updateFeedData($checkCreate['feed']->getId());

                if (!$checkUpdate['error']) {
                    $this->notification->setNotification(102);
                } else {
                    $this->notification->setNotification($checkUpdate['error']);
                }
            } else {
                $this->notification->setNotification($checkCreate['error']);
            }
        } else {
            $this->notification->setNotification(201);
        }
        $this->setNotificationMessage();

        return $this->createFormResponse($objectName, $routeName, $routeNameMany, $form);
    }

    /**
     * Edit process form of feeds
     * @param Request $request the current request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editProcess(Request $request)
    {
        //depends if it's edit or creation
        $objectId = $request->get('id');
        $objectName = 'Feed';
        $routeName = 'feed';
        $routeNameMany = 'feeds';
        $objectClass = 'NPS\ModelBundle\Entity\Feed';
        $objectTypeClass = 'NPS\FrontendBundle\Form\Type\FeedEditType';
        $form = $this->createFormEdit($objectId, $objectName, $objectClass, $objectTypeClass);
        $this->saveGenericForm($objectName, $form->bind($request));

        return $this->createFormResponse($objectName, $routeName, $routeNameMany, $form);
    }

    /**
     * Edit/create process form of feeds [POST]
     * Route defined in routing.yml
     * @param Request $request the current request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editProcessAction(Request $request)
    {
        if ($request->get('id')) {
            return $this->editProcess($request);
        } else {
            return $this->addProcess($request);
        }
    }

    /**
     * Change stat of feed: enabled/disabled
     * @param Request $request the current request
     *
     * @return Response
     */
    public function enabledStateAction(Request $request)
    {
        if ($this->get('security.context')->isGranted('ROLE_ADMIN')) {
            $objectName = 'Feed';
            $objectClass = 'NPS\ModelBundle\Entity\Feed';
            $check = $this->genericChangeObjectStatus($objectName, $objectClass, $request->get('id'));
        } else {
            $check = false;
        }

        if ($check) {
            return new RedirectResponse($this->router->generate('feeds'));
        } else {
            return new RedirectResponse($this->router->generate('login'));
        }
    }

}
