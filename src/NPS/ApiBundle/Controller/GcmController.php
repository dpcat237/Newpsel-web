<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * GcmController
 */
class GcmController extends Controller
{
    /**
     * Add new GCM registration ID
     *
     * @Rest\Post("/add_id")
     * @ApiDoc(
     *  description="Add new GCM registration ID",
     *  section="Google Cloud Messaging area",
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
     * @return JsonResponse
     */
    public function addRegistrationIdAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $deviceService = $this->get('api.device.service');
        $responseData = $deviceService->updateGcmId($json['appKey'], $json['gcm_id']);
        $response = new JsonResponse($responseData);

        return $response;
    }
}
