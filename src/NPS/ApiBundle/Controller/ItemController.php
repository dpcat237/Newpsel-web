<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * ItemController
 */
class ItemController extends ApiController
{
    /**
     * Sync articles
     *
     * @Rest\Post("/sync_unread")
     * @ApiDoc(
     *  description="List of unread articles",
     *  section="Article area",
     *  resource=true,
     *  statusCodes={
     *      200="Successfully",
     *      401="Authentication failed",
     *      405="Bad request method"
     *  },
     *  authentication=true,
     *  authenticationRoles={"ROLE_USER"},
     *  tags={"experimental"}
     * )
     *
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
        $responseData = $itemService->syncItems($json['appKey'], $json['items'], $json['limit']);
        if ($responseData['error']) {
            return $responseData['error'];
        }

        return $responseData['items'];
    }
}
