<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\SecurityContext;
use NPS\CoreBundle\Controller\CoreController;

/**
 * FeedController
 */
class ItemController extends CoreController
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
        $responseData = $itemService->syncUnreadItems($json['appKey'], $json['viewedItems'], $json['isDownload']);

        return new JsonResponse($responseData['unreadItems']);
    }
}
