<?php

namespace NPS\FrontendBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Request;
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

/**
 * ItemController
 */
class ItemController extends BaseController
{
    /**
     * List of items
     * @param Request  $request
     * @param UserFeed $userFeed
     *
     * @return array
     *
     * @Route("/feed/{user_feed_id}/items_list", name="items_list")
     * @Secure(roles="ROLE_USER")
     * @Template()
     *
     * @ParamConverter("userFeed", class="NPSCoreBundle:UserFeed", options={"id": "user_feed_id"})
     */
    public function listAction(Request $request, UserFeed $userFeed)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $itemRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:Item');
        $userItems = $itemRepo->getUnreadByFeedUser($user->getId(), $userFeed->getFeedId());
        $viewData = array(
            'userItems' => $userItems,
            'title' => $userFeed->getTitle(),
            'userFeedId' => $userFeed->getId()
        );

        return $viewData;
    }

    /**
     * List of items to read later
     * @param Later   $label
     *
     * @return array
     *
     * @Route("/label/{label_id}/items_list", name="items_later_list")
     * @Secure(roles="ROLE_USER")
     * @Template()
     *
     * @ParamConverter("label", class="NPSCoreBundle:Later", options={"id": "label_id"})
     */
    public function laterListAction(Later $label)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($label->getUserId() == $user->getId()) {
            $labelRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:Later');
            $itemsList = $labelRepo->getUnread($label->getId());

            $viewData = array(
                'items' => $itemsList,
                'title' => 'Label '.$label->getName()
            );

            return $viewData;
        }
    }

    /**
     * Show item
     * @param UserItem $userItem user's item
     * @param UserFeed $userFeed user's feed
     *
     * @return array
     *
     * @Route("/feed/{user_feed_id}/item/{user_item_id}", name="item_view")
     * @Secure(roles="ROLE_USER")
     * @Template()
     *
     * @ParamConverter("userItem", class="NPSCoreBundle:UserItem", options={"mapping": {"user_item_id": "id"}})
     * @ParamConverter("userFeed", class="NPSCoreBundle:UserFeed", options={"id": "user_feed_id"})
     */
    public function viewAction(UserItem $userItem, UserFeed $userFeed)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($user->getId() != $userItem->getUserId()) {
            $route = $this->container->get('router')->generate('items_list', array('user_feed_id' => $userFeed->getId()));
            return new RedirectResponse($route);
        }

        $this->get('item')->changeUserItemStatus($userItem, "isUnread", "setUnread", 2);
        $renderData = array(
            'userItem' => $userItem,
            'title' => $userFeed->getTitle()
        );

        return $renderData;
    }

    /**
     * Show item
     * @param LaterItem $laterItem
     *
     * @return array
     *
     * @Route("/label/{label_id}/item/{later_item_id}", name="item_later_view")
     * @Secure(roles="ROLE_USER")
     * @Template()
     *
     * @ParamConverter("laterItem", class="NPSCoreBundle:LaterItem", options={"mapping": {"later_item_id": "id", "label_id": "later"}})
     */
    public function viewLaterAction(LaterItem $laterItem)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($laterItem->getLater()->getUserId() == $user->getId()) {
            $laterItemService = $this->get('later_item');
            $item = $laterItemService->readItem($laterItem);
            $title = $laterItemService->getViewTitle($laterItem, $user);

            $renderData = array(
                'item' => $item,
                'title' => $title
            );

            return $renderData;
        }
    }

    /**
     * Change stat of item to read/unread
     * @param Request  $request  Request
     * @param UserItem $userItem user's item
     *
     * @return JsonResponse
     *
     * @Route("/feed/{user_feed_id}/item/{user_item_id}/mark_read/{status}", name="mark_read", defaults={"status" = null})
     * @Secure(roles="ROLE_USER")
     *
     * @ParamConverter("userItem", class="NPSCoreBundle:UserItem", options={"mapping": {"user_item_id": "id"}})
     */
    public function readAction(Request $request, UserItem $userItem)
    {
        $status = $request->get('status');
        $user = $this->get('security.context')->getToken()->getUser();
        if ($user->getId() != $userItem->getUserId()) {

            return new JsonResponse(false);
        }

        $status = $this->get('item')->changeUserItemStatus($userItem, "isUnread", "setUnread", $status);
        $result = ($status)? NotificationHelper::OK_IS_UNREAD : NotificationHelper::OK_IS_READ ;

        $response = array (
            'result' => $result
        );

        return new JsonResponse($response);
    }

    /**
     * Change stat of later item to read
     * @param LaterItem $laterItem
     *
     * @return JsonResponse
     *
     * @Route("/label/{label_id}/item/{later_item_id}/mark_read", name="mark_later_read")
     * @Secure(roles="ROLE_USER")
     *
     * @ParamConverter("laterItem", class="NPSCoreBundle:LaterItem", options={"mapping": {"later_item_id": "id", "label_id": "later"}})
     */
    public function readLaterAction(LaterItem $laterItem)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($laterItem->getLater()->getUserId() == $user->getId()) {
            $this->get('later_item')->makeLaterRead($laterItem);

            $item = $laterItem->getUserItem()->getItem();
            $this->get('item')->changeStatus($user, $item, "isUnread", "setUnread", 2);

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
     * @return JsonResponse
     *
     * @Route("/feed/{user_feed_id}/item/{user_item_id}/mark_star", name="mark_star")
     * @Secure(roles="ROLE_USER")
     *
     * @ParamConverter("userItem", class="NPSCoreBundle:UserItem", options={"mapping": {"user_item_id": "id"}})
     */
    public function starAction(UserItem $userItem)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($user->getId() != $userItem->getUserId()) {

            return new JsonResponse(false);
        }

        $status = $this->get('item')->changeUserItemStatus($userItem, "isStared", "setStared");
        $result =($status)? NotificationHelper::OK_IS_NOT_STARED : NotificationHelper::OK_IS_STARED ;

        $response = array (
            'result' => $result
        );

        return new JsonResponse($response);
    }
}
