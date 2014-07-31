<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use NPS\ApiBundle\Controller\ApiController;

/**
 * FeedController
 */
class ItemController extends ApiController
{
    /**
     * Sync items
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function syncUnreadAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $itemService = $this->get('api.item.service');
        $responseData = $itemService->syncItems($json['appKey'], $json['items'], $json['limit']);
        if ($responseData['error']) {
            return $this->plainResponse($responseData['error']);
        }

        return new JsonResponse($responseData['items']);
    }
}
