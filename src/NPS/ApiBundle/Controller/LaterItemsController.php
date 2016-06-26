<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;

/**
 * Class LaterItemsController
 *
 * @package NPS\ApiBundle\Controller
 */
class LaterItemsController extends ApiController
{
    /**
     * Add saved articles
     *
     * @Rest\Post("/add_saved")
     * @Rest\View
     *
     * @param Request $request the current request
     *
     * @return string
     */
    public function syncLaterAction(Request $request)
    {
        $deviceId     = $this->getDeviceId($request);
        $json         = json_decode($request->getContent(), true);
        $labelService = $this->get('api.later_item.service');
        $responseData = $labelService->syncLaterItemsApi($deviceId, $json['laterItems']);

        return $responseData['result'];
    }

    /**
     * Add shared articles
     *
     * @Rest\Post("/add_shared")
     * @Rest\View
     *
     * @param Request $request the current request
     *
     * @return string
     */
    public function syncSharedAction(Request $request)
    {
        $deviceId     = $this->getDeviceId($request);
        $json         = json_decode($request->getContent(), true);
        $labelService = $this->get('api.later_item.service');
        $responseData = $labelService->syncShared($deviceId, $json['sharedItems']);

        return $responseData['result'];
    }

    /**
     * Sync saved articles
     *
     * @Rest\Post("/sync")
     * @Rest\View
     *
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function syncLaterItemsAction(Request $request)
    {
        $deviceId     = $this->getDeviceId($request);
        $json         = json_decode($request->getContent(), true);
        $itemService  = $this->get('api.later_item.service');
        $responseData = $itemService->syncLaterItems($deviceId, $json['later_items'], $json['labels'], $json['limit']);

        return $responseData['later_items'];
    }

    /**
     * Sync later items to be dictated; from specific label
     *
     * @Rest\Post("/dictation/sync")
     * @Rest\View
     *
     * @param Request $request the current request
     *
     * @return string
     */
    public function syncDictateItemsAction(Request $request)
    {
        $deviceId     = $this->getDeviceId($request);
        $json         = json_decode($request->getContent(), true);
        $labelService = $this->get('api.later_item.service');
        $responseData = $labelService->syncDictateItems($deviceId, $json['items'], $json['limit']);

        return $responseData['result'];
    }
}
