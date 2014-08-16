<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class LaterItemsController
 *
 * @package NPS\ApiBundle\Controller
 */
class LaterItemsController extends ApiController
{
    /**
     * Sync new later items assigned to labels
     *
     * @param Request $request the current request
     *
     * @return string
     */
    public function syncLaterAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $labelService = $this->get('api.later_item.service');
        $responseData = $labelService->syncLaterItemsApi($json['appKey'], $json['laterItems']);
        if ($responseData['error']) {
            return $this->plainResponse($responseData['error']);
        }

        return $this->plainResponse($responseData['result']);
    }

    /**
     * Sync later items
     *
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function syncLaterItemsAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $itemService = $this->get('api.later_item.service');
        $responseData = $itemService->syncLaterItems($json['appKey'], $json['later_items'], $json['labels'], $json['limit']);
        if ($responseData['error']) {
            return $this->plainResponse($responseData['error']);
        }

        return new JsonResponse($responseData['later_items']);
    }

    /**
     * Sync shared items to create later items
     *
     * @param Request $request the current request
     *
     * @return string
     */
    public function syncSharedAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $labelService = $this->get('api.later_item.service');
        $responseData = $labelService->syncShared($json['appKey'], $json['sharedItems']);
        if ($responseData['error']) {
            return $this->plainResponse($responseData['error']);
        }

        return $this->plainResponse($responseData['result']);
    }

    /**
     * Sync later items to be dictated; from specific label
     *
     * @param Request $request the current request
     *
     * @return string
     */
    public function syncDictateItemsAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $labelService = $this->get('api.later_item.service');
        $responseData = $labelService->syncDictateItems($json['appKey'], $json['items'], $json['limit']);
        if ($responseData['error']) {
            return $this->plainResponse($responseData['error']);
        }

        return new JsonResponse($responseData['result']);
    }
}
