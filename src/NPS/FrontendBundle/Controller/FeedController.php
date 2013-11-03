<?php

namespace NPS\FrontendBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Entity\Feed,
    NPS\CoreBundle\Entity\UserFeed;
use NPS\FrontendBundle\Form\Type\UserFeedEditType;

/**
 * FeedController
 *
 * @Route("/feed")
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
        $userFeedRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:UserFeed');
        $itemRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:Item');
        $userFeeds = $userFeedRepo->getUserFeeds($user->getId());

        $feedsList = array();
        foreach ($userFeeds as $userFeed) {
            $addFeed['id'] = $userFeed->getId();
            $addFeed['title'] = $userFeed->getTitle();
            $addFeed['count'] = $itemRepo->countUnreadByFeedUser($user->getId(), $userFeed->getFeedId());
            $feedsList[] = $addFeed;
            $addFeed = null;
        }

        return array('userFeeds' =>  $feedsList);
    }

    /**
     * Create process form of feeds
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/add", name="feed_add")
     * @Secure(roles="ROLE_USER")
     */
    public function addAction(Request $request)
    {
        if (!$request->get('feed')) {
            $result = NotificationHelper::ERROR;
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            $downloadFeeds = $this->get('download_feeds');
            $checkCreate = $downloadFeeds->addFeed($request->get('feed'), $user);

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
     * Edit user's feed
     * @param Request  $request
     * @param UserFeed $userFeed
     *
     * @return Response
     *
     * @Route("/{feed_id}/edit", name="feed_edit")
     * @Secure(roles="ROLE_USER")
     * @Template()
     *
     * @ParamConverter("userFeed", class="NPSCoreBundle:UserFeed", options={"id": "feed_id"})
     */
    public function editAction(Request $request, UserFeed $userFeed)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $route = $this->container->get('router')->generate('feeds_list');
        if ($userFeed->getUserId() != $user->getId()) {
            return new RedirectResponse($route);
        }

        $formType = new UserFeedEditType($userFeed);
        $form = $this->createForm($formType, $userFeed);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            $this->get('feed')->saveFormUserFeed($form);

            return new RedirectResponse($route);
        }

        $viewData = array(
            'title' => 'Edit feed',
            'form' => $form->createView(),
            'userFeed' => $userFeed,
        );

        return $viewData;

    }

    /**
     * Create feed
     * @param UserFeed $userFeed
     *
     * @return Response
     *
     * @Route("/{feed_id}/delete", name="feed_delete")
     * @Secure(roles="ROLE_USER")
     *
     * @ParamConverter("userFeed", class="NPSCoreBundle:UserFeed", options={"id": "feed_id"})
     */
    public function deleteAction(UserFeed $userFeed)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $route = $this->container->get('router')->generate('feeds_list');
        if ($userFeed->getUserId() != $user->getId()) {
            return new RedirectResponse($route);
        }

        $this->get('feed')->removeUserFeed($userFeed);

        return new RedirectResponse($route);
    }

    /**
     * List of user's feeds
     *
     * @return array
     *
     * @Route("/list", name="feeds_list")
     * @Secure(roles="ROLE_USER")
     * @Template()
     */
    public function listAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $userFeedRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:UserFeed');
        $userFeeds = $userFeedRepo->getUserFeeds($user->getId());

        $viewData = array(
            'userFeeds' => $userFeeds,
            'title' => 'Feeds management'
        );

        return $viewData;
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
