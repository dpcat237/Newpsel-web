<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * ItemController
 */
class ItemController extends BaseController
{
    /**
     * List of items
     * @param Request $request the current request
     *
     * @return Response
     */
    public function listAction(Request $request)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('login'));
        } else {
            if ($feedId = $request->get('id')) {
                $user = $this->get('security.context')->getToken()->getUser();
                $feedSubscribed = $user->checkFeedExists($feedId);
                if ($feedSubscribed) {
                    $routeNameMany = 'items';
                    $orderBy = array('o.dateAdd' => 'DESC');
                    $where = array('feed' => $feedId);
                    $feedRepo = $this->em->getRepository('NPSModelBundle:Feed');
                    $itemRepo = $this->em->getRepository('NPSModelBundle:Item');
                    $feed = $feedRepo->find($feedId);
                    $objectCollection = $itemRepo->getListPagination(0, 0, $orderBy, $where);

                    $renderData = array(
                        'heading' => $feed->getTitle(),
                        'url_list' => $this->router->generate($routeNameMany),
                        $routeNameMany => $objectCollection,
                    );

                    return $this->render('NPSFrontendBundle:Item:list.html.twig', $renderData);
                }
            }

            return new RedirectResponse($this->router->generate('feeds'));
        }
    }

    /**
     * Show item
     * @param Request $request the current request
     *
     * @return Response
     */
    public function viewAction(Request $request)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('login'));
        } else {
            if ($itemId = $request->get('id')) {
                $user = $this->get('security.context')->getToken()->getUser();
                $itemRepo = $this->em->getRepository('NPSModelBundle:Item');
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
            $itemRepo = $this->em->getRepository('NPSModelBundle:Item');
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
     */
    public function starAction(Request $request)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate0('login'));
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            $itemRepo = $this->em->getRepository('NPSModelBundle:Item');
            $item = $itemRepo->find($request->get('itemId'));
            $itemRepo->changeStatus($user, $item, "IsStarred");

            return new RedirectResponse($this->router->generate('items', array('id' => $request->get('feedId'))));
        }
    }
}
