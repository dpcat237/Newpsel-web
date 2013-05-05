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
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('login'));
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            $objectName = 'Feed';
            $routeName = 'feed';
            $routeNameMany = 'feeds';
            $join = array('userFeeds uf' => 'o.if = uf.feedId'); //TODO:
            $where = array('uf.userId' => $user->getId());
            $orderBy = array('o.title' => 'ASC');

            return $this->genericListRender($objectName, $routeName, $routeNameMany, $orderBy);
        }
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
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('login'));
        } else {
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
        }
    }

    /**
     * Create process form of feeds
     * @param Request $request the current request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addProcess(Request $request)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('login'));
        } else {
            //depends if it's edit or creation
            $objectName = 'Feed';
            $objectClass = 'NPS\ModelBundle\Entity\Feed';
            $objectTypeClass = 'NPS\FrontendBundle\Form\Type\FeedAddType';
            $form = $this->createFormEdit(null, $objectName, $objectClass, $objectTypeClass);
            $form->bind($request);
            $this->createNotification($objectName);
            $formObject = $form->getData();

            if ($form->isValid()) {
                $user = $this->get('security.context')->getToken()->getUser();
                $feedRepo = $this->em->getRepository('NPSModelBundle:Feed');
                $rss = $this->get('fkr_simple_pie.rss');
                $feedRepo->setRss($rss);
                $checkCreate = $feedRepo->createFeed($formObject->getUrl(), $user);

                if (!$checkCreate['error']) {
                    /*$checkUpdate = $feedRepo->updateFeedData($checkCreate['feed']->getId());

                    if (!$checkUpdate['error']) {
                        $this->notification->setNotification(102);
                    } else {
                        $this->notification->setNotification($checkUpdate['error']);
                    }*/
                } else {
                    $this->notification->setNotification($checkCreate['error']);
                }
            } else {
                $this->notification->setNotification(201);
            }
            $this->setNotificationMessage();

            return new RedirectResponse($this->router->generate('feeds'));
        }
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
     * Sync all feeds
     * TODO: temp (later all with cron)
     * @return Response
     */
    public function syncAction()
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('login'));
        } else {
            $feedRepo = $this->em->getRepository('NPSModelBundle:Feed');
            $rss = $this->get('fkr_simple_pie.rss');
            $feedRepo->setRss($rss);
            $feeds = $feedRepo->findAll();
            foreach ($feeds as $feed) {
                $feedRepo->updateFeedData($feed->getId());
            }

            return new RedirectResponse($this->router->generate('feeds'));
        }
    }

}
