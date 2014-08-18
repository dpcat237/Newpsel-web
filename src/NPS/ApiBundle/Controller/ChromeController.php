<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Request;

/**
 * ChromeController
 */
class ChromeController extends Controller
{
    /**
     * Add new page/item to later
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function addPageAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $itemService = $this->get('api.later_item.service');
        $responseData = $itemService->addPage($json['appKey'], $json['labelId'], $json['webTitle'], $json['webUrl']);
        $response = new JsonResponse($responseData);

        return $response;
    }

    /**
     * Get user's labels
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function getLabelsAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $labelService = $this->get('api.label.service');
        $responseData = $labelService->getUserLabels($json['appKey']);
        $response = new JsonResponse($responseData);

        return $response;
    }

    /**
     * Login for Chrome extension
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function loginAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $deviceService = $this->get('api.device.service');
        $responseData = $deviceService->loginChromeApi($json['appKey']);
        $response = new JsonResponse($responseData);

        return $response;
    }

    /**
     * Request key for Chrome extension
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function requestKeyAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $deviceService = $this->get('api.device.service');
        $responseData = $deviceService->requestAppKey($json['email']);
        $response = new JsonResponse($responseData);

        return $response;
    }
}
