<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;

/**
 * GcmController
 */
class GcmController extends Controller
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
        $json          = json_decode($request->getContent(), true);
        $deviceService = $this->get('api.device.service');
        $responseData  = $deviceService->updateGcmId($json['appKey'], $json['gcm_id']);

        return $responseData;
    }
}
