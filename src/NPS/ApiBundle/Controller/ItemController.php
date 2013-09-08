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
class ItemController extends BaseController
{
    /**
     * Sync items
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function syncUnreadAction(Request $request)
    {
        $json = json_decode($request->getContent());
        $appKey = $json->appKey;
        $viewedItems = $json->viewedItems;
        $isDownload = $json->isDownload;

        $secure = $this->get('api_secure_service');
        $user = $secure->getUserByDevice($appKey);

        if ($user instanceof User) {
            $itemRepo = $this->em->getRepository('NPSCoreBundle:Item');
            if (is_array($viewedItems) && count($viewedItems)) {
                $itemRepo->syncViewedItems($user->getId(), $viewedItems);
            }
            $unreadItems = array();
            if ($isDownload) {
                $unreadItems = $itemRepo->getUnreadItemsApi($user->getId());
            }

            $response = new JsonResponse($unreadItems);

            return $response;
        }
        die(NotificationHelper::ERROR_NO_LOGGED);
    }

}
