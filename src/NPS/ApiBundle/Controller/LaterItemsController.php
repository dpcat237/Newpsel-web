<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * Class LaterItemsController
 *
 * @package NPS\ApiBundle\Controller
 */
class LaterItemsController extends ApiController
{
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
        $sharedArticles = (isset($json['shared_articles'])) ? $json['shared_articles'] : [];
        $labelService->syncShared($this->getDeviceUser($request), $sharedArticles);
    }

    /**
     * Get saved articles
     *
     * @Rest\Post("/list")
     * @Rest\View
     *
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        $this->getDeviceUser($request);
        $json = json_decode($request->getContent(), true);
        $itemService = $this->get('api.later_item.service');
        $items = isset($json['saved_articles']) ? $json['saved_articles'] : [];
        $tags = isset($json['return_tags']) ? $json['return_tags'] : [];
        $limit = isset($json['limit']) ? $json['limit'] : [];

        return $itemService->syncLaterItems($items, $tags, $limit);
    }

    /**
     * Sync saved articles changes
     *
     * @Rest\Post("/sync")
     * @Rest\View
     *
     * @param Request $request
     *
     * @return array
     */
    public function syncAction(Request $request)
    {
        $json         = json_decode($request->getContent(), true);
        $labelService = $this->get('api.later_item.service');
        $tagItems = (isset($json['saved_articles'])) ? $json['saved_articles'] : [];

        return $labelService->syncLaterItemsApi($this->getDeviceUser($request), $tagItems);
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
