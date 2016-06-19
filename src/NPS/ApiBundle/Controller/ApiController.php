<?php

namespace NPS\ApiBundle\Controller;

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ApiController
 */
class ApiController extends Controller
{
    /**
     * Add new page/item to later
     *
     * @Route("/doc/{version}")
     *
     * @param Request $request the current request
     *
     * @return Response
     */
    public function docAction($version)
    {
        $headers = ['Content-Type' => 'text/html'];
        try {
            $content = $this->container->get('templating')->render('NPSApiBundle:Api:documentation_'.$version.'.html.twig', []);
        } catch (Exception $e) {
            throw $this->createNotFoundException();
        }

        return new Response($content, Response::HTTP_OK, $headers);
    }
}
