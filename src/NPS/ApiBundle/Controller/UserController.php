<?php

namespace NPS\ApiBundle\Controller;

use NPS\CoreBundle\Helper\NotificationHelper;
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
        $deviceService = $this->get('api.device.service');
        $responseData = $deviceService->loginApi($json['appKey'], $json['email'], $json['password']);

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
        $deviceService = $this->get('api.device.service');
        $responseData = $deviceService->signUpApi($json['appKey'], $json['email'], $json['password']);

        return $this->plainResponse($responseData);
    }

    /**
     * Request password recovery
     *
     * @param Request $request
     *
     * @return Response
     */
    public function recoveryPasswordAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $userService = $this->get('nps.entity.user');
        $userService->requestRecoverPassword($json['email']);

        return $this->plainResponse(NotificationHelper::OK);
    }
}
