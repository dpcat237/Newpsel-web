<?php

namespace NPS\FrontendBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use NPS\CoreBundle\Constant\EntityConstants;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Template,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NPS\CoreBundle\Entity\Feed,
    NPS\CoreBundle\Entity\Item,
    NPS\CoreBundle\Entity\Later,
    NPS\CoreBundle\Entity\LaterItem,
    NPS\CoreBundle\Entity\UserFeed,
    NPS\CoreBundle\Entity\UserItem;
use NPS\CoreBundle\Helper\NotificationHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * ItemController
 */
class ItemController extends Controller
{
    /**
     * List of new items
     *
     * @param UserFeed $userFeed
     *
     * @Route("/feed/{user_feed_id}/items_list", name="items_list")
     * @Secure(roles="ROLE_USER")
     * @Template()     *
     * @ParamConverter("userFeed", class="NPSCoreBundle:UserFeed", options={"id": "user_feed_id"})
     *
     * @return array
     */
    public function listAction(UserFeed $userFeed)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $userItems = $this->getDoctrine()->getRepository('NPSCoreBundle:UserItem')->getByFeedUser($user->getId(), $userFeed->getFeedId());
        $labels = $this->getDoctrine()->getRepository('NPSCoreBundle:Later')->getUserLabel($user->getId());

        $viewData = array(
            'userItems' => $userItems,
            'title' => $userFeed->getTitle(),
            'userFeedId' => $userFeed->getId(),
            'labels' => $labels
        );

        return $viewData;
    }

    /**
     * List of seen items
     *
     * @param UserFeed $userFeed
     *
     * @Route("/feed/{user_feed_id}/items_list_read", name="items_list_read")
     * @Secure(roles="ROLE_USER")
     * @Template()
     * @ParamConverter("userFeed", class="NPSCoreBundle:UserFeed", options={"id": "user_feed_id"})
     *
     * @return array
     */
    public function listReadAction(UserFeed $userFeed)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $userItems = $this->getDoctrine()->getRepository('NPSCoreBundle:UserItem')->getByFeedUser($user->getId(), $userFeed->getFeedId(), false);
        $labels = $this->getDoctrine()->getRepository('NPSCoreBundle:Later')->getUserLabel($user->getId());

        $viewData = array(
            'userItems' => $userItems,
            'title' => $userFeed->getTitle(),
            'userFeedId' => $userFeed->getId(),
            'labels' => $labels
        );

        return $viewData;
    }

    /**
     * Mark as read all unread and show empty items list
     *
     * @param UserFeed $userFeed
     *
     * @Route("/feed/{user_feed_id}/items_mark_read", name="items_mark_all_read")
     * @Secure(roles="ROLE_USER")
     * @Template()
     * @ParamConverter("userFeed", class="NPSCoreBundle:UserFeed", options={"id": "user_feed_id"})
     *
     * @return array
     */
    public function markAllReadAction(UserFeed $userFeed)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $this->getDoctrine()->getRepository('NPSCoreBundle:UserItem')->markAllRead($user->getId(), $userFeed->getFeedId());

        return new RedirectResponse($this->container->get('router')->generate('items_list_read', array('user_feed_id' => $userFeed->getId())));
    }

    /**
     * List of new saved items
     *
     * @param Later $label
     *
     * @Route("/label/{label_id}/items_list", name="items_later_list")
     * @Secure(roles="ROLE_USER")
     * @Template()
     * @ParamConverter("label", class="NPSCoreBundle:Later", options={"id": "label_id"})
     *
     * @return array
     */
    public function laterListAction(Later $label)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($label->getUserId() == $user->getId()) {
            $labelItemRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:LaterItem');
            $itemsList = $labelItemRepo->getItems($label->getId());
            $labels = $this->getDoctrine()->getRepository('NPSCoreBundle:Later')->getUserLabel($user->getId());

            $viewData = array(
                'items' => $itemsList,
                'title' => 'Label '.$label->getName(),
                'labels' => $labels
            );

            return $viewData;
        }
    }

    /**
     * List of seen saved items
     *
     * @param Later $label
     *
     * @Route("/label/{label_id}/items_list_read", name="items_later_list_read")
     * @Secure(roles="ROLE_USER")
     * @Template()
     * @ParamConverter("label", class="NPSCoreBundle:Later", options={"id": "label_id"})
     *
     * @return array
     */
    public function laterListReadAction(Later $label)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($label->getUserId() == $user->getId()) {
            $labelItemRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:LaterItem');
            $itemsList = $labelItemRepo->getItems($label->getId(), false);
            $labels = $this->getDoctrine()->getRepository('NPSCoreBundle:Later')->getUserLabel($user->getId());

            $viewData = array(
                'items' => $itemsList,
                'title' => 'Label '.$label->getName(),
                'labels' => $labels
            );

            return $viewData;
        }
    }

    /**
     * Show item
     *
     * @param UserItem $userItem user's item
     * @param UserFeed $userFeed user's feed
     *
     * @Route("/feed/{user_feed_id}/item/{user_item_id}", name="item_view")
     * @Secure(roles="ROLE_USER")
     * @Template()     *
     * @ParamConverter("userItem", class="NPSCoreBundle:UserItem", options={"mapping": {"user_item_id": "id"}})
     * @ParamConverter("userFeed", class="NPSCoreBundle:UserFeed", options={"id": "user_feed_id"})
     *
     * @return array
     */
    public function viewAction(UserItem $userItem, UserFeed $userFeed)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($user->getId() != $userItem->getUserId()) {
            $route = $this->container->get('router')->generate('items_list', array('user_feed_id' => $userFeed->getFeedId()));

            return new RedirectResponse($route);
        }

        $this->get('nps.entity.user_item')->changeUserItemStatus($userItem, "isUnread", "setUnread", EntityConstants::STATUS_READ);
        $labels = $this->getDoctrine()->getRepository('NPSCoreBundle:Later')->getUserLabel($user->getId());
        $renderData = array(
            'userItem' => $userItem,
            'title' => $userFeed->getTitle(),
            'labels' => $labels
        );

        return $renderData;
    }

    /**
     * Show item
     *
     * @param LaterItem $laterItem
     *
     * @Route("/label/{label_id}/item/{later_item_id}", name="item_later_view")
     * @Secure(roles="ROLE_USER")
     * @Template()
     * @ParamConverter("laterItem", class="NPSCoreBundle:LaterItem", options={"mapping": {"later_item_id": "id", "label_id": "later"}})
     *
     * @return array
     */
    public function viewLaterAction(LaterItem $laterItem)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($laterItem->getLater()->getUserId() == $user->getId()) {
            $laterItemService = $this->get('nps.entity.later_item');
            $laterItemService->readItem($laterItem);
            $title = $laterItemService->getViewTitle($laterItem, $user);
            $labels = $this->getDoctrine()->getRepository('NPSCoreBundle:Later')->getUserLabel($user->getId());

            $renderData = array(
                'laterItem' => $laterItem,
                'title' => $title,
                'labels' => $labels
            );

            return $renderData;
        }
    }

    /**
     * Change stat of item to read/unread
     *
     * @param Request  $request  Request
     * @param UserItem $userItem user's item
     *
     * @return JsonResponse
     *
     * @Route("/feed/{user_feed_id}/item/{user_item_id}/mark_read/{status}", name="mark_read", defaults={"status" = null})
     * @Secure(roles="ROLE_USER")
     * @ParamConverter("userItem", class="NPSCoreBundle:UserItem", options={"mapping": {"user_item_id": "id"}})
     */
    public function readAction(Request $request, UserItem $userItem)
    {
        $status = $request->get('status');
        $user = $this->get('security.context')->getToken()->getUser();
        if ($user->getId() != $userItem->getUserId()) {

            return new JsonResponse(false);
        }

        $status = $this->get('nps.entity.user_item')->changeUserItemStatus($userItem, "isUnread", "setUnread", $status);
        $result = ($status)? NotificationHelper::OK_IS_UNREAD : NotificationHelper::OK_IS_READ ;

        $response = array (
            'result' => $result
        );

        return new JsonResponse($response);
    }

    /**
     * Unread user item and go to list
     *
     * @param Request  $request  Request
     * @param UserItem $userItem user's item
     *
     * @return JsonResponse
     *
     * @Route("/feed/{user_feed_id}/item/{user_item_id}/unread_to_list", name="unread_to_list")
     * @Secure(roles="ROLE_USER")
     * @ParamConverter("userItem", class="NPSCoreBundle:UserItem", options={"mapping": {"user_item_id": "id"}})
     */
    public function unreadToListAction(Request $request, UserItem $userItem)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($user->getId() == $userItem->getUserId()) {
            $this->get('nps.entity.user_item')->changeUserItemStatus($userItem, "isUnread", "setUnread", EntityConstants::STATUS_UNREAD);
        }

        return new RedirectResponse($this->get('router')->generate('items_list', array('user_feed_id' => $request->get('user_feed_id'))));
    }

    /**
     * Unread user later item and go to list
     *
     * @param Request   $request   Request
     * @param LaterItem $laterItem user's later item
     *
     * @return JsonResponse
     *
     * @Route("/item/{later_item_id}/unread_to_later_list", name="unread_to_later_list")
     * @Secure(roles="ROLE_USER")
     * @ParamConverter("laterItem", class="NPSCoreBundle:LaterItem", options={"mapping": {"later_item_id": "id"}})
     */
    public function unreadToLaterListAction(Request $request, LaterItem $laterItem)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($laterItem->getLater()->getUserId() == $user->getId()) {
            $this->get('nps.entity.later_item')->makeLaterRead($laterItem, EntityConstants::STATUS_UNREAD);
        }

        return new RedirectResponse($this->get('router')->generate('items_later_list', array('label_id' => $laterItem->getLaterId())));
    }

    /**
     * Change state of later item to read
     *
     * @param LaterItem $laterItem
     *
     * @Route("/item/{later_item_id}/mark_later_read", name="mark_later_read")
     * @Secure(roles="ROLE_USER")
     * @ParamConverter("laterItem", class="NPSCoreBundle:LaterItem", options={"mapping": {"later_item_id": "id"}})
     *
     * @return JsonResponse
     */
    public function readLaterAction(LaterItem $laterItem)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($laterItem->getLater()->getUserId() == $user->getId()) {
            $this->get('nps.entity.later_item')->makeLaterRead($laterItem);

            $item = $laterItem->getUserItem()->getItem();
            $this->get('nps.entity.user_item')->changeStatus($user, $item, "isUnread", "setUnread", EntityConstants::STATUS_READ);

            $renderData = array(
                'result' => NotificationHelper::OK_IS_READ
            );

            return new JsonResponse($renderData);
        }
    }

    /**
     * Add/remove star to item
     * @param UserItem $userItem user's item
     *
     * @Route("/item/{user_item_id}/mark_star", name="mark_star")
     * @Secure(roles="ROLE_USER")
     * @ParamConverter("userItem", class="NPSCoreBundle:UserItem", options={"mapping": {"user_item_id": "id"}})
     *
     * @return JsonResponse
     */
    public function starAction(UserItem $userItem)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($user->getId() != $userItem->getUserId()) {

            return new JsonResponse(false);
        }

        $status = $this->get('nps.entity.user_item')->changeUserItemStatus($userItem, "isStared", "setStared");
        $result =($status)? NotificationHelper::OK_IS_NOT_STARED : NotificationHelper::OK_IS_STARED ;

        $response = array (
            'result' => $result
        );

        return new JsonResponse($response);
    }

    /**
     * Add label to item or later item
     *
     * @param UserItem $userItem user's item
     * @param Later    $later    label
     *
     * @return JsonResponse
     *
     * @Route("/item/{user_item_id}/add_label/{label_id}", name="item_add_label")
     * @Secure(roles="ROLE_USER")
     * @Method("GET")
     * @ParamConverter("userItem", class="NPSCoreBundle:UserItem", options={"mapping": {"user_item_id": "id"}})
     * @ParamConverter("later", class="NPSCoreBundle:Later", options={"mapping": {"label_id": "id"}})
     */
    public function addLabelAction(UserItem $userItem, Later $later)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($user->getId() != $userItem->getUserId()) {
            return new JsonResponse(false);
        }

        $this->get('nps.entity.later_item')->addLaterItemCheck($userItem, $later);
        $this->get('nps.launcher.queue')->executeCrawling($user->getId());

        return new JsonResponse(true);
    }

    /**
     * Change state of later item to read
     *
     * @param UserItem $userItem user's item
     *
     * @Route("/item/{user_item_id}/mark_stared_read", name="mark_stared_read")
     * @Secure(roles="ROLE_USER")
     * @ParamConverter("userItem", class="NPSCoreBundle:UserItem", options={"mapping": {"user_item_id": "id"}})
     *
     * @return JsonResponse
     */
    public function staredShowInTabAction(UserItem $userItem)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($userItem->getUserId() == $user->getId()) {
            $this->get('nps.entity.user_item')->changeUserItemStatus($userItem, "isStared", "setStared", EntityConstants::STATUS_READ);

            $renderData = array(
                'result' => NotificationHelper::OK_IS_READ
            );

            return new JsonResponse($renderData);
        }
    }

    /**
     * List of favorite items
     *
     * @Route("/item/items_favorite_list", name="items_stared_list")
     * @Secure(roles="ROLE_USER")
     * @Template()
     *
     * @return array
     */
    public function staredListAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $userItems = $this->getDoctrine()->getRepository('NPSCoreBundle:UserItem')->getStaredItems($user->getId());
        $labels = $this->getDoctrine()->getRepository('NPSCoreBundle:Later')->getUserLabel($user->getId());

        $viewData = array(
            'items' => $userItems,
            'title' => $this->get('translator')->trans('_Stared_list'),
            'labels' => $labels
        );

        return $viewData;
    }

    /**
     * Show favorite item
     *
     * @param UserItem $userItem user's item
     *
     * @Route("/item/{user_item_id}/item_stared_view", name="item_stared_view")
     * @Secure(roles="ROLE_USER")
     * @Template()
     * @ParamConverter("userItem", class="NPSCoreBundle:UserItem", options={"mapping": {"user_item_id": "id"}})
     *
     * @return array
     */
    public function viewStaredAction(UserItem $userItem)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($user->getId() != $userItem->getUserId()) {
            $route = $this->container->get('router')->generate('items_stared_list');

            return new RedirectResponse($route);
        }

        $labels = $this->getDoctrine()->getRepository('NPSCoreBundle:Later')->getUserLabel($user->getId());
        $renderData = array(
            'userItem' => $userItem,
            'title' => $this->get('translator')->trans('_Stared_view'),
            'labels' => $labels
        );

        return $renderData;
    }

    /**
     * Remove favorite from item
     *
     * @param UserItem $userItem user's item
     *
     * @return JsonResponse
     *
     * @Route("/item/{user_item_id}/unread_to_stared_list", name="unread_to_stared_list")
     * @Secure(roles="ROLE_USER")
     * @ParamConverter("userItem", class="NPSCoreBundle:UserItem", options={"mapping": {"user_item_id": "id"}})
     */
    public function unreadToStaredListAction(UserItem $userItem)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($userItem->getUserId() == $user->getId()) {
            $this->get('nps.entity.user_item')->changeUserItemStatus($userItem, "isStared", "setStared", EntityConstants::STATUS_READ);
        }

        return new RedirectResponse($this->get('router')->generate('items_stared_list'));
    }
}