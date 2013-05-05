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
                $routeNameMany = 'entries';
                $orderBy = array('dateAdd' => 'DESC');
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
            } else {
                return new RedirectResponse($this->router->generate('feeds'));
            }
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
                $entryRepo = $this->em->getRepository('NPSModelBundle:Entry');
                $entry = $entryRepo->find($entryId);
                $objectClass = 'NPS\ModelBundle\Entity\Entry';
                $this->genericChangeObjectStatus('Entry', $objectClass, $entryId, 'IsRead');

                $renderData = array(
                    'heading' => $entry->getTitle(),
                    'entry' => $entry
                );

                return $this->render('NPSFrontendBundle:Entry:view.html.twig', $renderData);
            } else {
                return new RedirectResponse($this->router->generate('homepage'));
            }
        }
    }

    /**
     * Change stat of entry to read/unread
     * @param Request $request the current request
     *
     * @return Response
     */
    public function readAction(Request $request)
    {
        $objectName = 'Entry';
        $objectClass = 'NPS\ModelBundle\Entity\Entry';
        $function = 'IsRead';
        $this->genericChangeObjectStatus($objectName, $objectClass, $request->get('entryId'), $function);

        return new RedirectResponse($this->router->generate('entries', array('id' => $request->get('feedId'))));
    }

    /**
     * Add/remove star to entry
     * @param Request $request the current request
     *
     * @return Response
     */
    public function starAction(Request $request)
    {
        $objectName = 'Entry';
        $objectClass = 'NPS\ModelBundle\Entity\Entry';
        $function = 'IsStarred';
        $this->genericChangeObjectStatus($objectName, $objectClass, $request->get('entryId'), $function);

        return new RedirectResponse($this->router->generate('entries', array('id' => $request->get('feedId'))));
    }
}
