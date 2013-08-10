<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Entity\Item;
use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Entity\UserItem;
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
     * @Route("/feed/{feed_id}/items_list", name="items_list")
     * @Template()
     * @ParamConverter("feed", class="NPSCoreBundle:Feed", options={"id": "feed_id"})
     */
    public function listAction(Request $request, Feed $feed)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('welcome'));
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            $itemRepo = $this->em->getRepository('NPSCoreBundle:Item');
            $itemsList = $itemRepo->getUnreadByFeedUser($user->getId(), $request->get('feed_id'));
            $viewData = array(
                'items' => $itemsList,
                'title' => $feed->getTitle()
            );

            return $viewData;
        }
    }

    /**
     * List of items to read later
     * @param Later   $label
     *
     * @return array
     * @Route("/label/{label_id}/items_list", name="items_later_list")
     * @Template()
     * @ParamConverter("label", class="NPSCoreBundle:Later", options={"id": "label_id"})
     */
    public function laterListAction(Later $label)
    {
        if ($this->get('security.context')->isGranted('ROLE_USER')) {
            $user = $this->get('security.context')->getToken()->getUser();
            if ($label->getUserId() == $user->getId()) {
                $labelRepo = $this->em->getRepository('NPSCoreBundle:Later');
                $itemsList = $labelRepo->getUnread($label->getId());

                $viewData = array(
                    'items' => $itemsList,
                    'title' => 'Label '.$label->getName()
                );

                return $viewData;
            }
        }

        return new RedirectResponse($this->router->generate('welcome'));
    }

    /**
     * Show item
     * @param Item    $item
     *
     * @return array
     * @Route("/feed/{feed_id}/item/{item_id}", name="item_view")
     * @Template()
     * @ParamConverter("item", class="NPSCoreBundle:Item", options={"mapping": {"item_id": "id", "feed_id": "feed"}})
     */
    public function viewAction(Item $item)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('welcome'));
        }

        $user = $this->get('security.context')->getToken()->getUser();
        $itemRepo = $this->em->getRepository('NPSCoreBundle:Item');
        $itemRepo->changeStatus($user, $item, "isUnread", "setIsUnread", 2);
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
     * @Route("/label/{label_id}/item/{item_id}", name="item_later_view")
     * @Template()
     * @ParamConverter("laterItem", class="NPSCoreBundle:LaterItem", options={"mapping": {"item_id": "id", "label_id": "later"}})
     */
    public function viewLaterAction(LaterItem $laterItem)
    {
        if ($this->get('security.context')->isGranted('ROLE_USER')) {
            $user = $this->get('security.context')->getToken()->getUser();
            if ($laterItem->getLater()->getUserId() == $user->getId()) {
                $laterItem->setIsUnread(false);
                $this->em->persist($laterItem);
                $this->em->flush();

                $item = $laterItem->getUserItem()->getItem();
                $itemRepo = $this->em->getRepository('NPSCoreBundle:Item');
                $itemRepo->changeStatus($user, $item, "isUnread", "setIsUnread", 2);

                $renderData = array(
                    'item' => $item,
                    'title' => $item->getFeed()->getTitle()
                );

                return $renderData;
            }
        }

        return new RedirectResponse($this->router->generate('welcome'));
    }

    /**
     * Change stat of item to read/unread
     * @param Request $request
     * @param Item    $item
     *
     * @return JsonResponse
     * @Route("/feed/{feed_id}/item/{item_id}/mark_read/{status}", name="mark_read", defaults={"status" = null})
     * @ParamConverter("item", class="NPSCoreBundle:Item", options={"mapping": {"item_id": "id", "feed_id": "feed"}})
     */
    public function readAction(Request $request, Item $item)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('welcome'));
        } else {
            $status = $request->get('status');
            $user = $this->get('security.context')->getToken()->getUser();
            $itemRepo = $this->em->getRepository('NPSCoreBundle:Item');
            $status = $itemRepo->changeStatus($user, $item, "isUnread", "setIsUnread", $status);
            $result=($status)? NotificationHelper::OK_IS_UNREAD : NotificationHelper::OK_IS_READ ;

            $response = array (
                'result' => $result
            );

            return new JsonResponse($response);
        }
    }

    /**
     * Change stat of later item to read
     * @param LaterItem $laterItem
     *
     * @return JsonResponse
     * @Route("/label/{label_id}/item/{item_id}/mark_read", name="mark_later_read")
     * @ParamConverter("laterItem", class="NPSCoreBundle:LaterItem", options={"mapping": {"item_id": "id", "label_id": "later"}})
     */
    public function readLaterAction(LaterItem $laterItem)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('welcome'));
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            if ($laterItem->getLater()->getUserId() == $user->getId()) {
                $laterItem->setIsUnread(false);
                $this->em->persist($laterItem);
                $this->em->flush();

                $item = $laterItem->getUserItem()->getItem();
                $itemRepo = $this->em->getRepository('NPSCoreBundle:Item');
                $itemRepo->changeStatus($user, $item, "isUnread", "setIsUnread", 2);

                $renderData = array(
                    'result' => NotificationHelper::OK_IS_READ
                );

                return new JsonResponse($renderData);
            }
        }
    }

    /**
     * Add/remove star to item
     * @param Request $request
     * @param Item    $item
     *
     * @return JsonResponse
     * @Route("/feed/{feed_id}/item/{item_id}/mark_star", name="mark_star")
     * @ParamConverter("item", class="NPSCoreBundle:Item", options={"mapping": {"item_id": "id", "feed_id": "feed"}})
     */
    public function starAction(Request $request, Item $item)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('welcome'));
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            $itemRepo = $this->em->getRepository('NPSCoreBundle:Item');
            $status = $itemRepo->changeStatus($user, $item, "isStared", "setIsStared");
            $result=($status)? NotificationHelper::OK_IS_NOT_STARED : NotificationHelper::OK_IS_STARED ;

            $response = array (
                'result' => $result
            );

            return new JsonResponse($response);
        }
    }
}
