<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use NPS\ApiBundle\Controller\ApiController;

/**
 * UserController
 */
class UserController extends ApiController
{
    /**
     * List of feeds
     *
     * @param Request $request the current request
     *
     * @return Response
     */
    public function loginAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $itemService = $this->get('api.device.service');
        $responseData = $itemService->loginApi($json['appKey'], $json['email'], $json['password']);

        return $this->plainResponse($responseData);
    }

    /**
     * Sign up an user
     *
     * @param Request $request
     * 
     * @return Response
     */
    public function signUpAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $itemService = $this->get('api.device.service');
        $responseData = $itemService->signUpApi($json['appKey'], $json['email'], $json['password']);

        return $this->plainResponse($responseData);
    }
}
