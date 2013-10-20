<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\JsonResponse;
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
     * @Secure(roles="ROLE_USER")
     * @Template()
     */
    public function menuAction()
    {
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

    /**
     * Edit/create form of feeds [GET]
     * Route defined in routing.yml
     * @param Request $request the current request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_USER")
     */
    public function editAction(Request $request)
    {
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

    /**
     * Create process form of feeds
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/add_feed", name="add_feed")
     * @Secure(roles="ROLE_USER")
     */
    protected function addProcess(Request $request)
    {
        if (!$request->get('feed')) {
            $result = NotificationHelper::ERROR;
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            $downloadFeeds = $this->get('download_feeds');
            $checkCreate = $downloadFeeds->createFeed($request->get('feed'), $user);

            if ($checkCreate['error']) {
                $this->get('system_notification')->setMessage($checkCreate['error']);
            }

            $result = NotificationHelper::OK;
        }

        $response = array (
            'result' => $result
        );

        return new JsonResponse($response);
    }

    /**
     * Edit process form of feeds
     * @param Request $request the current request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function editProcess(Request $request)
    {
        $objectId = $request->get('id');
        $objectName = 'Feed';
        $routeName = 'feed';
        $routeNameMany = 'feeds';
        $objectClass = 'NPS\CoreBundle\Entity\Feed';
        $objectTypeClass = 'NPS\FrontendBundle\Form\Type\FeedEditType';
        $form = $this->createFormEdit($objectId, $objectName, $objectClass, $objectTypeClass);
        $this->get('feed')->saveFormFeed($form);

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
     *
     * @return Response
     *
     * @Secure(roles="ROLE_USER")
     */
    public function syncAction()
    {
        $downloadFeeds = $this->container->get('download_feeds');
        $route = $this->container->get('router')->generate('feeds');
        $feedRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:Feed');

        $feeds = $feedRepo->findAll();
        foreach ($feeds as $feed) {
            $downloadFeeds->updateFeedData($feed->getId());
        }

        return new RedirectResponse($route);
    }

}
