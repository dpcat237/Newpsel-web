<?php

namespace NPS\ApiBundle\Controller;

use NPS\CoreBundle\Helper\NotificationHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * UserController
 */
class UserController extends ApiController
{
    /**
     * Login
     *
     * @Rest\Post("/login")
     * @ApiDoc(
     *  description="Login",
     *  section="User area",
     *  resource=true,
     *  statusCodes={
     *      200="Successfully",
     *      401="Authentication failed",
     *      405="Bad request method"
     *  },
     *  authentication=true,
     *  authenticationRoles={"ROLE_USER"},
     *  tags={"experimental"}
     * )
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
     * @Rest\Post("/sign_up")
     * @ApiDoc(
     *  description="Sign up",
     *  section="User area",
     *  resource=true,
     *  statusCodes={
     *      200="Successfully",
     *      401="Authentication failed",
     *      405="Bad request method"
     *  },
     *  authentication=true,
     *  authenticationRoles={"ROLE_USER"},
     *  tags={"experimental"}
     * )
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
     * @Rest\Post("/password_recovery")
     * @ApiDoc(
     *  description="Request password recovery",
     *  section="User area",
     *  resource=true,
     *  statusCodes={
     *      200="Successfully",
     *      401="Authentication failed",
     *      405="Bad request method"
     *  },
     *  authentication=true,
     *  authenticationRoles={"ROLE_USER"},
     *  tags={"experimental"}
     * )
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
