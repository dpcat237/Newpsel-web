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
        $json = json_decode($request->getContent(), true);
        $this->getFeedApiService()->addFeed($this->getDeviceUser($request), $json['feed_url']);
    }

    /**
     * Edit feed title
     *
     * @Rest\Post("/edit")
     * @Rest\View
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function editFeedAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $this->getFeedApiService()->editFeed($this->getDeviceUser($request), $json['feed_id'], $json['feed_title']);
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
        $json = json_decode($request->getContent(), true);
        $feeds = (isset($json['feeds'])) ? $json['feeds'] : [];
        $responseData = $this->getFeedApiService()->syncFeeds($this->getDeviceUser($request), $feeds);
        if ($responseData['error']) {
            return $responseData['error'];
        }

        return $responseData['feeds'];
    }

    /**
     * Unsubscribe from feed
     *
     * @Rest\Delete("/{id}/delete")
     * @Rest\View
     *
     * @param int     $id
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteFeedAction($id, Request $request)
    {
        $this->getFeedApiService()->unsubscribeFeed($this->getDeviceUser($request), $id);
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
        $this->getDeviceId($request);
        $sources = $this->getSourceApiService()->getPopularSources();

        return $this->get('nps.api.source.transformer')->transformList($sources);
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
