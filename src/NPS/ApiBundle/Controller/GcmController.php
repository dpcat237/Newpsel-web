<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * GcmController
 */
class GcmController extends ApiController
{
    /**
     * Add new GCM registration ID
     *
     * @Rest\Post("/add_id")
     * @Rest\View
     *
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function addRegistrationIdAction(Request $request)
    {
        $deviceId     = $this->getDeviceId($request);
        $json          = json_decode($request->getContent(), true);
        $deviceService = $this->get('api.device.service');
        $responseData  = $deviceService->updateGcmId($deviceId, $json['gcm_id']);

        return $responseData;
    }
}
