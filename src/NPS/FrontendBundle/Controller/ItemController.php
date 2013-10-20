<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Entity\Item;
use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Entity\Later;
use NPS\CoreBundle\Entity\LaterItem;

/**
 * ItemController
 */
class ItemController extends BaseController
{
    /**
     * List of items
     * @param Request $request
     * @param Feed    $feed
     *
     * @return array
     *
     * @Route("/feed/{feed_id}/items_list", name="items_list")
     * @Secure(roles="ROLE_USER")
     * @Template()
     *
     * @ParamConverter("feed", class="NPSCoreBundle:Feed", options={"id": "feed_id"})
     */
    public function listAction(Request $request, Feed $feed)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $itemRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:Item');
        $itemsList = $itemRepo->getUnreadByFeedUser($user->getId(), $request->get('feed_id'));
        $viewData = array(
            'items' => $itemsList,
            'title' => $feed->getTitle()
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
     * @param Item    $item
     *
     * @return array
     *
     * @Route("/feed/{feed_id}/item/{item_id}", name="item_view")
     * @Secure(roles="ROLE_USER")
     * @Template()
     *
     * @ParamConverter("item", class="NPSCoreBundle:Item", options={"mapping": {"item_id": "id", "feed_id": "feed"}})
     */
    public function viewAction(Item $item)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $this->get('item')->changeStatus($user, $item, "isUnread", "setIsUnread", 2);
        $renderData = array(
            'item' => $item,
            'title' => $item->getFeed()->getTitle()
        );

        return $renderData;
    }

    /**
     * Show item
     * @param LaterItem $laterItem
     *
     * @return array
     *
     * @Route("/label/{label_id}/item/{item_id}", name="item_later_view")
     * @Secure(roles="ROLE_USER")
     * @Template()
     *
     * @ParamConverter("laterItem", class="NPSCoreBundle:LaterItem", options={"mapping": {"item_id": "id", "label_id": "later"}})
     */
    public function viewLaterAction(LaterItem $laterItem)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($laterItem->getLater()->getUserId() == $user->getId()) {
            $item = $this->get('later_item')->readItem($laterItem);
            $title = ($item->getFeed() instanceof Feed)? $item->getFeed()->getTitle() : $laterItem->getLater()->getName();

            $renderData = array(
                'item' => $item,
                'title' => $title
            );

            return $renderData;
        }
    }

    /**
     * Change stat of item to read/unread
     * @param Request $request
     * @param Item    $item
     *
     * @return JsonResponse
     *
     * @Route("/feed/{feed_id}/item/{item_id}/mark_read/{status}", name="mark_read", defaults={"status" = null})
     * @Secure(roles="ROLE_USER")
     *
     * @ParamConverter("item", class="NPSCoreBundle:Item", options={"mapping": {"item_id": "id", "feed_id": "feed"}})
     */
    public function readAction(Request $request, Item $item)
    {
        $status = $request->get('status');
        $user = $this->get('security.context')->getToken()->getUser();
        $status = $this->get('item')->changeStatus($user, $item, "isUnread", "setIsUnread", $status);
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
     * @Route("/label/{label_id}/item/{item_id}/mark_read", name="mark_later_read")
     * @Secure(roles="ROLE_USER")
     *
     * @ParamConverter("laterItem", class="NPSCoreBundle:LaterItem", options={"mapping": {"item_id": "id", "label_id": "later"}})
     */
    public function readLaterAction(LaterItem $laterItem)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($laterItem->getLater()->getUserId() == $user->getId()) {
            $this->get('later_item')->makeLaterRead($laterItem);

            $item = $laterItem->getUserItem()->getItem();
            $this->get('item')->changeStatus($user, $item, "isUnread", "setIsUnread", 2);

            $renderData = array(
                'result' => NotificationHelper::OK_IS_READ
            );

            return new JsonResponse($renderData);
        }
    }

    /**
     * Add/remove star to item
     * @param Item    $item
     *
     * @return JsonResponse
     *
     * @Route("/feed/{feed_id}/item/{item_id}/mark_star", name="mark_star")
     * @Secure(roles="ROLE_USER")
     *
     * @ParamConverter("item", class="NPSCoreBundle:Item", options={"mapping": {"item_id": "id", "feed_id": "feed"}})
     */
    public function starAction(Item $item)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $status = $this->get('item')->changeStatus($user, $item, "isStared", "setIsStared");
        $result =($status)? NotificationHelper::OK_IS_NOT_STARED : NotificationHelper::OK_IS_STARED ;

        $response = array (
            'result' => $result
        );

        return new JsonResponse($response);
    }
}
