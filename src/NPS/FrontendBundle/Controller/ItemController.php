<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * ItemController
 */
class ItemController extends BaseController
{
    /**
     * List of items
     * @param Request $request
     *
     * @return Response
     * @Route("/feed/{feed_id}/items_list", name="items_list")
     */
    public function listAction(Request $request)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('welcome'));
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            $itemRepo = $this->em->getRepository('NPSCoreBundle:Item');
            $itemsList = $itemRepo->getUnreadByFeedUser($user->getId(), $request->get('feed_id'));

            $viewData = array(
                'items' => $itemsList,
            );

            return $this->render('NPSFrontendBundle:Item:list.html.twig', $viewData);
        }
    }

    /**
     * Show item
     * @param Request $request
     *
     * @return Response
     * @Route("/feed/{feed_id}/item/{item_id}", name="item_view")
     */
    public function viewAction(Request $request)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('login'));
        } else {
            if ($itemId = $request->get('id')) {
                $user = $this->get('security.context')->getToken()->getUser();
                $itemRepo = $this->em->getRepository('NPSCoreBundle:Item');
                $item = $itemRepo->find($itemId);

                if ($itemRepo->canSee($user->getId(), $itemId)) {
                    $itemRepo->changeStatus($user, $item, "IsUnread", 2);

                    $renderData = array(
                        'heading' => $item->getTitle(),
                        'item' => $item
                    );

                    return $this->render('NPSFrontendBundle:Item:view.html.twig', $renderData);
                }
            }
        }

        return new RedirectResponse($this->router->generate('homepage'));
    }

    /**
     * Change stat of item to read/unread
     * @param Request $request the current request
     *
     * @return Response
     */
    public function readAction(Request $request)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate0('login'));
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            $itemRepo = $this->em->getRepository('NPSCoreBundle:Item');
            $item = $itemRepo->find($request->get('itemId'));
            $itemRepo->changeStatus($user, $item, "IsUnread", 2);

            return new RedirectResponse($this->router->generate('items', array('id' => $request->get('feedId'))));
        }
    }

    /**
     * Add/remove star to item
     * @param Request $request the current request
     *
     * @return Response
     * @Route("/feed/{feed_id}/item/{item_id}", name="mark_star")
     */
    public function starAction(Request $request)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate0('login'));
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            $itemRepo = $this->em->getRepository('NPSCoreBundle:Item');
            $item = $itemRepo->find($request->get('itemId'));
            $itemRepo->changeStatus($user, $item, "IsStarred");

            return new RedirectResponse($this->router->generate('items', array('id' => $request->get('feedId'))));
        }
    }
}
