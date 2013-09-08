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
        $response = false;
        $json = json_decode($request->getContent(), true);

        if (isset($json['appKey']) && $json['appKey']) {
            $secure = $this->get('api_secure_service');
            $user = $secure->getUserByDevice($json['appKey']);
            if ($user instanceof User) {
                $labelRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:Later');
                $orderBy = array('name' => 'ASC');
                $labelsData = $labelRepo->findByUser($user, $orderBy);

                //prepare labels for api
                foreach ($labelsData as $lab) {
                    $label['id'] = $lab->getId();
                    $label['name'] = $lab->getName();
                    $labels[] = $label;
                }
                $response = true;
            }
        }
        $responseData = array(
            'response' => $response,
            'labels' => $labels
        );
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
        $response = false;
        $json = json_decode($request->getContent(), true);

        if (isset($json['username']) && $json['username']) {
            $userRepo = $this->em->getRepository('NPSCoreBundle:User');
            $user = $userRepo->findOneByUsername($json['username']);

            if($user instanceof User){
                $userNotification = $this->get('user.notifications');
                $deviceRepo = $this->em->getRepository('NPSCoreBundle:Device');
                $extensionKey = substr(hash("sha1", uniqid(rand(), true)), 0, 16);

                //send email to user with new key
                $userNotification->sendChromeKey($user, $extensionKey);
                //save new key for extension
                $deviceRepo->createDevice($extensionKey, $user);

                $response = true;
            }
        }
        $response = new JsonResponse(array('response' => $response));

        return $response;
    }
}
