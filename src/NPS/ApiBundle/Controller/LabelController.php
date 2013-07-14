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
class LabelController extends BaseController
{
    /**
     * List of labels
     * @param Request $request the current request
     *
     * @return Response
     */
    public function syncLabelsAction(Request $request)
    {
        $json = json_decode($request->getContent());
        $appKey = $json->appKey;
        $changedLabels = $json->changedLabels;
        $lastUpdate = $json->lastUpdate;

        if ($appKey) {
            $userRepo = $this->em->getRepository('NPSCoreBundle:User');
            $cache = $this->container->get('server_cache');
            if ($userRepo->checkLogged($cache, $appKey)) {
                $user = $userRepo->getDeviceUser($cache, $appKey);
                $labelRepo = $this->em->getRepository('NPSCoreBundle:Later');

                if (count($changedLabels)) {
                    $createdIds = $labelRepo->syncLabels($user, $changedLabels);
                    $labelCollection = $labelRepo->getUserLabelsApiCreated($user->getId(), $lastUpdate, $changedLabels, $createdIds);
                } else {
                    $labelCollection = $labelRepo->getUserLabelsApi($user->getId(), $lastUpdate);
                }

                $jsonData = json_encode($labelCollection);
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
     * List of feeds
     * @param Request $request the current request
     *
     * @return Response
     */
    public function syncLaterAction(Request $request)
    {
        $json = json_decode($request->getContent());
        $appKey = $json->appKey;
        $laterItems = $json->laterItems;

        if ($appKey) {
            $userRepo = $this->em->getRepository('NPSCoreBundle:User');
            $cache = $this->container->get('server_cache');
            if ($userRepo->checkLogged($cache, $appKey)) {
                $user = $userRepo->getDeviceUser($cache, $appKey);
                if (is_array($laterItems) && count($laterItems)) {
                    $labelRepo = $this->em->getRepository('NPSCoreBundle:Later');
                    $labelRepo->syncLaterItems($user->getId(), $laterItems);

                    echo NotificationHelper::OK; exit();
                } else {
                    echo NotificationHelper::ERROR_NO_DATA; exit();
                }
            } else {
                echo NotificationHelper::ERROR_NO_LOGGED; exit();
            }
        }
        echo NotificationHelper::ERROR_NO_APP_KEY; exit();
    }
}
