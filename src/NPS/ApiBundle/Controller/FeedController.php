<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\SecurityContext;
use NPS\CoreBundle\Controller\CoreController;

/**
 * FeedController
 */
class FeedController extends CoreController
{
    /**
     * List of feeds
     * @param Request $request the current request
     *
     * @return JsonResponse | string
     */
    public function syncFeedsAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $feedService = $this->get('api.feed.service');
        $responseData = $feedService->syncFeeds($json['appKey'], $json['lastUpdate']);

        return new JsonResponse($responseData['feedCollection']);
    }

    /**
     * Add feed, subscribe user to this feed and add last items for user
     * @param Request $request
     *
     * @return JsonResponse | string
     */
    public function addFeedAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $feedService = $this->get('api.feed.service');
        $responseData = $feedService->addFeed($json['appKey'], $json['feed_url']);

        return new JsonResponse($responseData['unreadItems']);
    }
}
