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
class ItemController extends BaseController
{
    /**
     * List of feeds
     * @param Request $request the current request
     *
     * @return Response
     */
    public function syncUnreadAction(Request $request)
    {
        $json = json_decode($request->getContent());
        $appKey = $json->appKey;
        $viewedFeeds = $json->viewedFeeds;
        $isDownload = $json->isDownload;

        if ($appKey) {
            $userRepo = $this->em->getRepository('NPSModelBundle:User');
            $cache = $this->container->get('server_cache');
            if ($userRepo->checkLogged($cache, $appKey)) {
                $itemRepo = $this->em->getRepository('NPSModelBundle:Item');
                $user = $userRepo->getDeviceUser($cache, $appKey);
                if (count($viewedFeeds)) {
                    $itemRepo->syncViewedItems($user->getId(), $viewedFeeds);
                }
                $unreadItems = array();
                if ($isDownload) {
                    $unreadItems = $itemRepo->getUnreadItemsApi($user->getId());
                }

                $jsonData = json_encode($unreadItems);
                $headers = array('Content-Type' => 'application/json');
                $response = new Response($jsonData, 200, $headers);

                return $response;
            } else {
                echo NotificationHelper::ERROR_NO_LOGGED; exit();
            }
        }
        echo NotificationHelper::ERROR_NO_APP_KEY; exit();
    }

}
