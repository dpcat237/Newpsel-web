<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

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
     * @Rest\Post("/sync_later")
     * @ApiDoc(
     *  description="Sync new later items assigned to labels",
     *  section="Saved articles area",
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
     * Sync later items
     *
     * @Rest\Post("/sync_later_items")
     * @ApiDoc(
     *  description="Sync later items",
     *  section="Saved articles area",
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
     * Sync shared items to create later items
     *
     * @Rest\Post("/sync_shared")
     * @ApiDoc(
     *  description="Sync shared items to create later items",
     *  section="Saved articles area",
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
     * Sync later items to be dictated; from specific label
     *
     * @Rest\Post("/sync_dictate_items")
     * @ApiDoc(
     *  description="ync later items to be dictated;",
     *  section="Saved articles area",
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
