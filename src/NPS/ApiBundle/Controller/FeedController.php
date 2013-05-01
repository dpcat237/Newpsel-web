<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use NPS\ApiBundle\Controller\BaseController;

/**
 * FeedController
 */
class FeedController extends BaseController
{
    /**
     * List of feeds
     * @param Request $request the current request
     *
     * @return Response
     */
    public function syncAction(Request $request)
    {
        /*if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
            return new RedirectResponse($this->router->generate('login'));
        } else {*/

        $feedRepo = $this->em->getRepository('NPSModelBundle:Feed');
        $feedCollection = $feedRepo->getFeedArray();

        $jsonData = json_encode($feedCollection);
        $headers = array('Content-Type' => 'application/json');
        $response = new Response($jsonData, 200, $headers);

        return $response;
        //}
    }

}
