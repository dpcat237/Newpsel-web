<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use NPS\ApiBundle\Controller\ApiController;

/**
 * LabelController
 */
class LabelController extends ApiController
{
    /**
     * List of labels
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function syncLabelsAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $labelService = $this->get('api.label.service');
        $responseData = $labelService->syncLabels($json['appKey'], $json['changedLabels'], $json['lastUpdate']);
        if ($responseData['error']) {
            return $this->plainResponse($responseData['error']);
        }

        return new JsonResponse($responseData['labelCollection']);
    }

    /**
     * Sync later items to save in web selected item to read later
     *
     * @param Request $request the current request
     *
     * @return string
     */
    public function syncLaterAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $labelService = $this->get('api.label.service');
        $responseData = $labelService->syncLaterItemsApi($json['appKey'], $json['laterItems']);
        if ($responseData['error']) {
            return $this->plainResponse($responseData['error']);
        }

        return $this->plainResponse($responseData['result']);
    }

    /**
     * Add new pages/items to later
     *
     * @param Request $request the current request
     *
     * @return string
     */
    public function syncSharedAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $labelService = $this->get('api.item.service');
        $responseData = $labelService->syncShared($json['appKey'], $json['sharedItems']);
        if ($responseData['error']) {
            return $this->plainResponse($responseData['error']);
        }

        return $this->plainResponse($responseData['result']);
    }

    /**
     * Sync later articles to be dictated
     *
     * @param Request $request the current request
     *
     * @return string
     */
    public function syncDictateItemsAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $labelService = $this->get('api.label.service');
        $responseData = $labelService->syncDictateItems($json['appKey'], $json['items'], $json['limit']);
        if ($responseData['error']) {
            return $this->plainResponse($responseData['error']);
        }

        return new JsonResponse($responseData['result']);
    }
}
