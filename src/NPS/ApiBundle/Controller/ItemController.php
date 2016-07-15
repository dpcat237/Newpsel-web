<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * ItemController
 */
class ItemController extends ApiController
{
    /**
     * Sync articles
     *
     * @Rest\Post("/sync")
     * @Rest\RequestParam(name="appKey", strict=true, description="Device Identifier")
     * @Rest\RequestParam(name="items", strict=true, description="Device Identifier")
     * @Rest\RequestParam(name="limit", strict=true, requirements="\d+", description="Limit for items")
     * @Rest\View
     *
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function syncItemsAction(Request $request)
    {
        $json         = json_decode($request->getContent(), true);
        $itemService  = $this->get('api.item.service');
        $responseData = $itemService->syncItems($this->getDeviceUser($request), $json['items'], $json['limit']);
        if ($responseData['error']) {
            return $responseData['error'];
        }

        return $responseData['items'];
    }
}
