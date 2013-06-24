<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * FeedController
 */
class FeedController extends BaseController
{
    /**
     * Menu build
     *
     * @Template()
     */
    public function menuAction()
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('welcome'));
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            $em = $this->getDoctrine()->getManager();
            $feedRepo = $em->getRepository('NPSCoreBundle:Feed');
            $itemRepo = $em->getRepository('NPSCoreBundle:Item');
            $feedsCollection = $feedRepo->getUserFeeds($user->getId());
            $feedsList = array();
            foreach ($feedsCollection as $feed) {
                $addFeed['id'] = $feed->getId();
                $addFeed['title'] = $feed->getTitle();
                $addFeed['count'] = $itemRepo->countUnreadByFeedUser($user->getId(), $feed->getId());
                $feedsList[] = $addFeed;
                $addFeed = null;
            }

            return array('feeds' =>  $feedsList);
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
            $objectClass = 'NPS\CoreBundle\Entity\Feed';
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
     * @param Request $request
     *
     * @return Response
     * @Route("/add_feed", name="add_feed")
     */
    public function addProcess(Request $request)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER') || !$request->get('feed')) {
            $result = NotificationHelper::ERROR;
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            $this->createNotification("Feed");
            $feedRepo = $this->em->getRepository('NPSCoreBundle:Feed');
            $rss = $this->get('fkr_simple_pie.rss');
            $cache = $this->get('server_cache');
            $feedRepo->setRss($rss);
            $feedRepo->setCache($cache);
            $checkCreate = $feedRepo->createFeed($request->get('feed'), $user);

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
            $this->setNotificationMessage();

            $result = NotificationHelper::OK;
        }

        $response = array (
            'result' => $result
        );

        return new Response(json_encode($response));
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
        $objectClass = 'NPS\CoreBundle\Entity\Feed';
        $objectTypeClass = 'NPS\FrontendBundle\Form\Type\FeedEditType';
        $form = $this->createFormEdit($objectId, $objectName, $objectClass, $objectTypeClass);
        $this->saveGenericForm($objectName, $form->handleRequest($request));

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
            $feedRepo = $this->em->getRepository('NPSCoreBundle:Feed');
            $rss = $this->get('fkr_simple_pie.rss');
            $cache = $this->get('server_cache');
            $feedRepo->setRss($rss);
            $feedRepo->setCache($cache);
            $feeds = $feedRepo->findAll();
            foreach ($feeds as $feed) {
                $feedRepo->updateFeedData($feed->getId());
            }

            return new RedirectResponse($this->router->generate('feeds'));
        }
    }

}
