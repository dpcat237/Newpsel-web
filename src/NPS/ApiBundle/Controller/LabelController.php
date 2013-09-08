<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\SecurityContext;
use NPS\ApiBundle\Controller\BaseController;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Entity\User;

/**
 * LabelController
 */
class LabelController extends BaseController
{
    /**
     * List of labels
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function syncLabelsAction(Request $request)
    {
        $json = json_decode($request->getContent());
        $appKey = $json->appKey;
        $changedLabels = $json->changedLabels;
        $lastUpdate = $json->lastUpdate;

        $secure = $this->get('api_secure_service');
        $user = $secure->getUserByDevice($appKey);

        if ($user instanceof User) {
            $labelRepo = $this->em->getRepository('NPSCoreBundle:Later');

            if (count($changedLabels)) {
                $createdIds = $labelRepo->syncLabels($user, $changedLabels);
                $labelCollection = $labelRepo->getUserLabelsApiCreated($user->getId(), $lastUpdate, $changedLabels, $createdIds);
            } else {
                $labelCollection = $labelRepo->getUserLabelsApi($user->getId(), $lastUpdate);
            }
            $response = new JsonResponse($labelCollection);

            return $response;
        }
        die(NotificationHelper::ERROR_NO_LOGGED);
    }

    /**
     * List of feeds
     * @param Request $request the current request
     *
     * @return string
     */
    public function syncLaterAction(Request $request)
    {
        $json = json_decode($request->getContent()); //adding param true -> will be array!
        $appKey = $json->appKey;
        $laterItems = $json->laterItems;

        $secure = $this->get('api_secure_service');
        $user = $secure->getUserByDevice($appKey);

        if ($user instanceof User) {
            if (is_array($laterItems) && count($laterItems)) {
                $labelRepo = $this->em->getRepository('NPSCoreBundle:Later');
                $labelRepo->syncLaterItems($user->getId(), $laterItems);

                echo NotificationHelper::OK; exit();
            } else {
                echo NotificationHelper::ERROR_NO_DATA; exit();
            }
        }
        die(NotificationHelper::ERROR_NO_LOGGED);
    }
}
