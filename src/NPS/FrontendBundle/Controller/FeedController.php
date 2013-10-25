<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\JsonResponse;
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
