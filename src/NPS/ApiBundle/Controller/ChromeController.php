<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use NPS\ApiBundle\Controller\BaseController;
use NPS\CoreBundle\Entity\User;

/**
 * ChromeController
 */
class ChromeController extends BaseController
{
    /**
     * Add new page/item to later
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function addPageAction(Request $request)
    {
        $response = false;
        $json = json_decode($request->getContent(), true);

        if (isset($json['appKey']) && $json['appKey']) {
            $secure = $this->get('api_secure_service');
            $user = $secure->getUserByDevice($json['appKey']);
            if ($user instanceof User) {
                $itemService = $this->get('item');
                $itemService->addPageToLater($user, $json['labelId'], $json['webTitle'], $json['webUrl']);
                $response = true;
            }
        }
        $responseData = array(
            'response' => $response
        );
        $response = new JsonResponse($responseData);

        return $response;
    }

    /**
     * Get user's labels
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function getLabelsAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $chromeData = $this->get('chrome_data_service');
        $responseData = $chromeData->getUserLabels($json['appKey']);
        $response = new JsonResponse($responseData);

        return $response;
    }

    /**
     * Login for Chrome extension
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function loginAction(Request $request)
    {
        $response = false;
        $json = json_decode($request->getContent(), true);

        if (isset($json['appKey']) && $json['appKey']) {
            $secure = $this->get('api_secure_service');

            $user = $secure->getUserByDevice($json['appKey']);
            if ($user instanceof User) {
                $response = true;
            }
        }
        $response = new JsonResponse(array('response' => $response));

        return $response;
    }

    /**
     * Request key for Chrome extension
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function requestKeyAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $chromeData = $this->get('chrome_data_service');
        $responseData = $chromeData->requestAppKey($json['username']);
        $response = new JsonResponse($responseData);

        return $response;
    }
}
