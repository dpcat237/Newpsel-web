<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Request;

/**
 * GcmController
 */
class GcmController extends Controller
{
    /**
     * Add new GCM registration ID
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
