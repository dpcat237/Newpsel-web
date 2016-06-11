<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * ChromeController
 */
class ChromeController extends Controller
{
    /**
     * Add new page/item to later
     *
     * @Rest\Post("/add")
     * @ApiDoc(
     *  description="Add new page/item to later",
     *  section="Chrome area",
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
    public function addPageAction(Request $request)
    {
        $json         = json_decode($request->getContent(), true);
        $itemService  = $this->get('api.later_item.service');
        $responseData = $itemService->addPage($json['appKey'], $json['labelId'], $json['webTitle'], $json['webUrl']);

        return $responseData;
    }

    /**
     * Get user's labels
     *
     * @Rest\Post("/labels")
     * @ApiDoc(
     *  description="Get user's labels",
     *  section="Chrome area",
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
    public function getLabelsAction(Request $request)
    {
        $json         = json_decode($request->getContent(), true);
        $labelService = $this->get('api.label.service');
        $responseData = $labelService->getUserLabels($json['appKey']);

        return $responseData;
    }

    /**
     * Login for Chrome extension
     *
     * @Rest\Post("/login")
     * @ApiDoc(
     *  description="Login for Chrome extension",
     *  section="Chrome area",
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
    public function loginAction(Request $request)
    {
        $json          = json_decode($request->getContent(), true);
        $deviceService = $this->get('api.device.service');
        $responseData  = $deviceService->loginChromeApi($json['appKey']);

        return $responseData;
    }

    /**
     * Request key for Chrome extension
     *
     * @Rest\Post("/request")
     * @ApiDoc(
     *  description="Request a key for Chrome extension",
     *  section="Chrome area",
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
    public function requestKeyAction(Request $request)
    {
        $json          = json_decode($request->getContent(), true);
        $deviceService = $this->get('api.device.service');
        $responseData  = $deviceService->requestAppKey($json['email']);

        return $responseData;
    }
}
