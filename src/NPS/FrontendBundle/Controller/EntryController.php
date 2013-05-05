<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * EntryController
 */
class EntryController extends BaseController
{
    /**
     * List of entries
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
                    $routeNameMany = 'entries';
                    $orderBy = array('o.dateAdd' => 'DESC');
                    $where = array('feed' => $feedId);
                    $feedRepo = $this->em->getRepository('NPSModelBundle:Feed');
                    $entryRepo = $this->em->getRepository('NPSModelBundle:Entry');
                    $feed = $feedRepo->find($feedId);
                    $objectCollection = $entryRepo->getListPagination(0, 0, $orderBy, $where);

                    $renderData = array(
                        'heading' => $feed->getTitle(),
                        'url_list' => $this->router->generate($routeNameMany),
                        $routeNameMany => $objectCollection,
                    );

                    return $this->render('NPSFrontendBundle:Entry:list.html.twig', $renderData);
                }
            }

            return new RedirectResponse($this->router->generate('feeds'));
        }
    }

    /**
     * Show entry
     * @param Request $request the current request
     *
     * @return Response
     */
    public function viewAction(Request $request)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('login'));
        } else {
            if ($entryId = $request->get('id')) {
                $user = $this->get('security.context')->getToken()->getUser();
                $entryRepo = $this->em->getRepository('NPSModelBundle:Entry');
                $entry = $entryRepo->find($entryId);

                if ($entryRepo->canSee($user->getId(), $entryId)) {
                    $entryRepo->changeStatus($user, $entry, "IsUnread", 2);

                    $renderData = array(
                        'heading' => $entry->getTitle(),
                        'entry' => $entry
                    );

                    return $this->render('NPSFrontendBundle:Entry:view.html.twig', $renderData);
                }
            }
        }

        return new RedirectResponse($this->router->generate('homepage'));
    }

    /**
     * Change stat of entry to read/unread
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
            $entryRepo = $this->em->getRepository('NPSModelBundle:Entry');
            $entry = $entryRepo->find($request->get('entryId'));
            $entryRepo->changeStatus($user, $entry, "IsUnread", 2);

            return new RedirectResponse($this->router->generate('entries', array('id' => $request->get('feedId'))));
        }
    }

    /**
     * Add/remove star to entry
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
            $entryRepo = $this->em->getRepository('NPSModelBundle:Entry');
            $entry = $entryRepo->find($request->get('entryId'));
            $entryRepo->changeStatus($user, $entry, "IsStarred");

            return new RedirectResponse($this->router->generate('entries', array('id' => $request->get('feedId'))));
        }
    }
}
