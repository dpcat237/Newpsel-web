<?php

namespace NPS\ApiBundle\Controller;

use NPS\ApiBundle\Services\Entity\FeedApiService;
use NPS\ApiBundle\Services\Entity\SourceApiService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;

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
        $deviceId     = $this->getDeviceId($request);
        $json = json_decode($request->getContent(), true);
        $this->getFeedApiService()->addFeed($deviceId, $json['feed_url']);
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
        $deviceId     = $this->getDeviceId($request);
        $json         = json_decode($request->getContent(), true);
        $responseData = $this->getFeedApiService()->syncFeeds($deviceId, $json['feeds']);
        if ($responseData['error']) {
            return $responseData['error'];
        }

        return $responseData['feeds'];
    }

    /**
     * List popular sources by categories
     *
     * @Rest\Get("/sources")
     * @Rest\View
     *
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function getSourcesAction(Request $request)
    {
        $this->getDeviceKey($request);
        $tis = $this->getSourceApiService()->getPopularSources();

        return $this->get('nps.api.source.transformer')->transformList($tis);
    }

    /**
     * @return FeedApiService
     */
    protected function getFeedApiService()
    {
        return $this->get('api.feed.service');
    }

    /**
     * @return SourceApiService
     */
    protected function getSourceApiService()
    {
        return $this->get('api.source.service');
    }
}
