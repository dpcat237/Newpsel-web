<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;

/**
 * FeedController
 */
class FeedController extends ApiController
{
    /**
     * Add feed, subscribe user to this feed and add last items for user
     *
     * @Rest\Post("/add")
     * @Rest\View
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addFeedAction(Request $request)
    {
        $json         = json_decode($request->getContent(), true);
        $feedService  = $this->get('api.feed.service');
        $responseData = $feedService->addFeed($json['appKey'], $json['feed_url']);

        return $responseData;
    }

    /**
     * List of feeds
     *
     * @Rest\Post("/sync")
     * @Rest\View
     *
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function syncFeedsAction(Request $request)
    {
        $json         = json_decode($request->getContent(), true);
        $feedService  = $this->get('api.feed.service');
        $responseData = $feedService->syncFeeds($json['appKey'], $json['feeds']);
        if ($responseData['error']) {
            return $responseData['error'];
        }

        return $responseData['feeds'];
    }
}
