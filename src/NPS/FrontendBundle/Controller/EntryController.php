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
        /*if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
            return new RedirectResponse($this->router->generate('login'));
        } else {*/
        if ($request->get('id')) {

            $objectName = 'Entry';
            $routeName = 'entry';
            $routeNameMany = 'entries';
            $orderBy = array('dateAdd' => 'DESC');
            $where = array('feed' => $request->get('id'));

            return $this->genericListRender($objectName, $routeName, $routeNameMany, $orderBy, $where);
        } else {
            return new RedirectResponse($this->router->generate('feeds'));
        }
        //}
    }


}
