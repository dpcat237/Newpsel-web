<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\SecurityContext;
use NPS\ApiBundle\Controller\BaseController;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Entity\User;

/**
 * FeedController
 */
class FeedController extends BaseController
{
    /**
     * List of feeds
     * @param Request $request the current request
     *
     * @return JsonResponse | string
     */
    public function syncFeedsAction(Request $request)
    {
        $json = json_decode($request->getContent());
        $appKey = $json->appKey;
        $lastUpdate = $json->lastUpdate;

        $secure = $this->get('api_secure_service');
        $user = $secure->getUserByDevice($appKey);

        if ($user instanceof User) {
            $feedRepo = $this->em->getRepository('NPSCoreBundle:Feed');
            $feedCollection = $feedRepo->getUserFeedsApi($user->getId(), $lastUpdate);

            return new JsonResponse($feedCollection);
        }
        die(NotificationHelper::ERROR_NO_LOGGED);
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
