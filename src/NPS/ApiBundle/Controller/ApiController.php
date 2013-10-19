<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use NPS\CoreBundle\Controller\CoreController;

/**
 * ApiController
 */
class ApiController extends CoreController
{
    /**
     * Prepare plain text response
     * @param $data
     *
     * @return Response
     */
    protected function plainResponse($data)
    {
        $viewData = array(
            'response' => $data
        );

        $response = new Response();
        $response->headers->set('Content-Type', 'text/plain');
        $response->sendHeaders();

        $render = $this->render('NPSApiBundle:Api:response.html.twig', $viewData, $response);

        return $render;
    }
}
