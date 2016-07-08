<?php

namespace NPS\ApiBundle\Controller;

use Exception;
use NPS\ApiBundle\Exception\UnauthorizedException;
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

    /**
     * Get device ID from headers
     *
     * @param Request $request
     *
     * @return array|string
     */
    protected function getDeviceId(Request $request)
    {
        $deviceId = $request->headers->get('deviceId');
        if (!$deviceId) {
            $json          = json_decode($request->getContent(), true);
            $deviceId =(isset($json['deviceId']))? $json['deviceId'] : null;
        }

        if (!$deviceId) {
            throw new UnauthorizedException();
        }

        return $deviceId;
    }

    /**
     * Get user from device ID
     *
     * @param Request $request
     *
     * @return mixed
     */
    protected function getDeviceUser(Request $request)
    {
        $deviceId = $this->getDeviceId($request);

        return $this->get('api.secure.service')->getUserByDevice($deviceId);
    }
}
