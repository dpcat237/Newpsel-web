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
        $json         = json_decode($request->getContent(), true);
        $labelService = $this->get('api.later_item.service');
        $responseData = $labelService->syncLaterItemsApi($json['appKey'], $json['laterItems']);
        if ($responseData['error']) {
            return $responseData['error'];
        }

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
        $json         = json_decode($request->getContent(), true);
        $labelService = $this->get('api.later_item.service');
        $responseData = $labelService->syncShared($json['appKey'], $json['sharedItems']);
        if ($responseData['error']) {
            return $responseData['error'];
        }

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
        $json         = json_decode($request->getContent(), true);
        $itemService  = $this->get('api.later_item.service');
        $responseData = $itemService->syncLaterItems($json['appKey'], $json['later_items'], $json['labels'], $json['limit']);
        if ($responseData['error']) {
            return $responseData['error'];
        }

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
        $json         = json_decode($request->getContent(), true);
        $labelService = $this->get('api.later_item.service');
        $responseData = $labelService->syncDictateItems($json['appKey'], $json['items'], $json['limit']);
        if ($responseData['error']) {
            return $responseData['error'];
        }

        return $responseData['result'];
    }
}
