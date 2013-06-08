<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use NPS\ApiBundle\Controller\BaseController;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * FeedController
 */
class FeedController extends BaseController
{
    /**
     * List of feeds
     * @param Request $request the current request
     *
     * @return Response
     */
    public function syncFeedsAction(Request $request)
    {
        $json = json_decode($request->getContent());
        $appKey = $json->appKey;
        $lastUpdate = $json->lastUpdate;

        if ($appKey) {
            $userRepo = $this->em->getRepository('NPSModelBundle:User');
            $cache = $this->container->get('server_cache');
            if ($userRepo->checkLogged($cache, $appKey)) {
                $user = $userRepo->getDeviceUser($cache, $appKey);
                $feedRepo = $this->em->getRepository('NPSModelBundle:Feed');
                $feedCollection = $feedRepo->getUserFeedsApi($user->getId(), $lastUpdate);

                $jsonData = json_encode($feedCollection);
                $headers = array('Content-Type' => 'application/json');
                $response = new Response($jsonData, 200, $headers);

                return $response;
            } else {
                echo NotificationHelper::ERROR_NO_LOGGED; exit();
            }
        }
        echo NotificationHelper::ERROR_NO_APP_KEY; exit();
    }

    /**
     * Add feed, subscribe user to this feed and add last items for user
     * @param Request $request
     */
    public function addFeedAction(Request $request)
    {
        $json = json_decode($request->getContent());
        $appKey = $json->appKey;
        $feedUrl = $json->feed_url;

        if ($appKey) {
            $userRepo = $this->em->getRepository('NPSModelBundle:User');
            $cache = $this->container->get('server_cache');
            if ($userRepo->checkLogged($cache, $appKey)) {
                $user = $userRepo->getDeviceUser($cache, $appKey);
                $feedRepo = $this->em->getRepository('NPSModelBundle:Feed');
                $rss = $this->get('fkr_simple_pie.rss');
                $cache = $this->get('server_cache');
                $feedRepo->setRss($rss);
                $feedRepo->setCache($cache);
                $checkCreate = $feedRepo->createFeed($feedUrl, $user);

                if (!$checkCreate['error']) {
                    echo NotificationHelper::OK; exit();
                } else {
                    echo $checkCreate['error']; exit();
                }
            } else {
                echo NotificationHelper::ERROR_NO_LOGGED; exit();
            }
        }
        echo NotificationHelper::ERROR_NO_APP_KEY; exit();
    }
}
