<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\SecurityContext;
use NPS\CoreBundle\Controller\CoreController;

/**
 * LabelController
 */
class LabelController extends CoreController
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
        $responseData = $labelService->syncFeeds($json['appKey'], $json['changedLabels'], $json['lastUpdate']);

        return new JsonResponse($responseData['feedCollection']);
    }

    /**
     * List of feeds
     * @param Request $request the current request
     *
     * @return string
     */
    public function syncLaterAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $labelService = $this->get('api.label.service');
        $responseData = $labelService->syncLaterItems($json['appKey'], $json['laterItems']);

        return new JsonResponse($responseData['result']);
    }

    /**
     * Add new pages/items to later
     * @param Request $request the current request
     *
     * @return string
     */
    public function syncSharedAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $labelService = $this->get('api.item.service');
        $responseData = $labelService->syncShared($json['appKey'], $json['sharedItems']);

        return new JsonResponse($responseData['result']);
    }
}
