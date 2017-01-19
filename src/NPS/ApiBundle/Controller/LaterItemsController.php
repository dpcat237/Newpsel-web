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
        $responseData = $labelService->syncLaterItemsApi($this->getDeviceUser($request), $json['tagItems']);

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
        $responseData = $labelService->syncShared($this->getDeviceUser($request), $json['sharedItems']);

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
        $items = (isset($json['tag_items'])) ? $json['tag_items'] : [];
        $tags = (isset($json['tags'])) ? $json['tags'] : [];
        $limit = (isset($json['limit'])) ? $json['limit'] : [];
        $responseData = $itemService->syncLaterItems($this->getDeviceUser($request), $items, $tags, $limit);

        return $responseData['tag_items'];
    }

    /**
     * Sync saved items to be dictated; from specific tag
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
        $responseData = $labelService->syncDictateItems($this->getDeviceUser($request), $json['items'], $json['limit']);

        return $responseData['result'];
    }
}
