<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * FeedController
 */
class FeedController extends ApiController
{
    /**
     * List of feeds
     *
     * @Rest\Post("/sync_feeds")
     * @ApiDoc(
     *  description="List of feeds",
     *  section="Feed area",
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

    /**
     * Add feed, subscribe user to this feed and add last items for user
     *
     * @Rest\Post("/add_feed")
     * @ApiDoc(
     *  description="Subscribe user to the feed",
     *  section="Feed area",
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
}
