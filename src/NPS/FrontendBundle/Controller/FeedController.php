<?php

namespace NPS\FrontendBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use NPS\CoreBundle\Event\FeedCreatedEvent;
use NPS\CoreBundle\Event\FeedModifiedEvent;
use NPS\CoreBundle\NPSCoreEvents;
use NPS\FrontendBundle\Form\Type\ImportOpmlType;
use NPS\FrontendBundle\Services\Entity\FeedFrontendService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Entity\UserFeed;
use NPS\FrontendBundle\Form\Type\UserFeedEditType;

/**
 * FeedController
 *
 * @Route("/feed")
 */
class FeedController extends Controller
{
    /**
     * Menu build
     *
     * @Secure(roles="ROLE_USER")
     * @Template()
     */
    public function menuAction()
    {
        $user         = $this->getUser();
        $userFeedRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:UserItem');
        $menuAll      = $this->getFeedFrontendService()->getMenuAll($user->getId());
        if ($menuAll) {
            $feedsList  = $userFeedRepo->getUserFeedsForMenu($user->getId(), $menuAll);
            $feedsCount = $userFeedRepo->getUserFeedsForMenu($user->getId());
        } else {
            $feedsList  = $userFeedRepo->getUserFeedsForMenu($user->getId(), $menuAll);
            $feedsCount = array();
        }


        $response = array(
            'userFeeds'      => $feedsList,
            'userFeedsCount' => $feedsCount,
            'menuAll'        => $menuAll
        );

        return $response;
    }

    /**
     * Create process form of feeds
     *
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/add", name="feed_add")
     * @Secure(roles="ROLE_USER")
     */
    public function addAction(Request $request)
    {
        $error = false;
        if (!$request->get('feed')) {
            $result      = NotificationHelper::ERROR;
            $itemListUrl = '';
        } else {
            $user          = $this->getUser();
            $downloadFeeds = $this->get('download_feeds');
            list($feed, $error) = $downloadFeeds->addFeed($request->get('feed'), $user);
        }

        if ($error) {
            $this->get('system_notification')->setMessage($error);
        } else {
            $result      = NotificationHelper::OK;
            $userFeed    = $this->getFeedFrontendService()->getUserFeed($user->getId(), $feed->getId());
            $itemListUrl = $this->container->get('router')->generate('items_list', array('user_feed_id' => $userFeed->getId()), true);

            //notify other devices about modification
            $this->get('event_dispatcher')->dispatch(NPSCoreEvents::FEED_MODIFIED, new FeedModifiedEvent($user->getId()));
        }
        $response = array(
            'result' => $result,
            'url'    => $itemListUrl
        );

        return new JsonResponse($response);
    }

    /**
     * Edit user's feed
     *
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
        $user  = $this->getUser();
        $route = $this->container->get('router')->generate('feeds_list');
        if ($userFeed->getUserId() != $user->getId()) {
            return new RedirectResponse($route);
        }

        $form = $this->createForm(UserFeedEditType::class, $userFeed);
        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            $this->getFeedFrontendService()->saveFormUserFeed($form);

            //notify other devices about modification
            $this->get('event_dispatcher')->dispatch(NPSCoreEvents::FEED_MODIFIED, new FeedModifiedEvent($user->getId()));

            return new RedirectResponse($route);
        }

        $viewData = array(
            'title'    => 'Edit feed',
            'form'     => $form->createView(),
            'userFeed' => $userFeed
        );

        return $viewData;

    }

    /**
     * Delete feed
     *
     * @param UserFeed $userFeed
     *
     * @return RedirectResponse
     *
     * @Route("/{feed_id}/delete", name="feed_delete")
     * @Secure(roles="ROLE_USER")
     *
     * @ParamConverter("userFeed", class="NPSCoreBundle:UserFeed", options={"id": "feed_id"})
     */
    public function deleteAction(UserFeed $userFeed)
    {
        $user  = $this->getUser();
        $route = $this->container->get('router')->generate('feeds_list');
        if ($userFeed->getUserId() != $user->getId()) {
            return new RedirectResponse($route);
        }

        $this->getFeedFrontendService()->removeUserFeed($userFeed);

        //notify other devices about modification
        $this->get('event_dispatcher')->dispatch(NPSCoreEvents::FEED_MODIFIED, new FeedModifiedEvent($user->getId()));

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
        $user         = $this->getUser();
        $userFeedRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:UserFeed');
        $userFeeds    = $userFeedRepo->getUserFeeds($user->getId());

        $viewData = array(
            'userFeeds' => $userFeeds,
            'title'     => 'Feeds management',
            'menuAll'   => $this->getFeedFrontendService()->getMenuAll($user->getId())
        );

        return $viewData;
    }

    /**
     * Change status if show all feeds in menu or only with new items
     *
     * @Route("/change_menu_all", name="change_menu_all_feeds")
     * @Secure(roles="ROLE_USER")
     */
    public function changeAllFeedsStatusAction()
    {
        $this->getFeedFrontendService()->changeMenuAll($this->getUser()->getId());

        return new RedirectResponse($this->container->get('router')->generate('feeds_list'));
    }

    /**
     * Get FeedFrontendService
     *
     * @return FeedFrontendService
     */
    protected function getFeedFrontendService()
    {
        return $this->get('nps.frontend.entity.feed');
    }
}
