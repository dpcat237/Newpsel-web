<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use NPS\ApiBundle\Controller\BaseController;
use NPS\ModelBundle\Entity\User;
use NPS\ModelBundle\Entity\Device;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * FeedController
 */
class UserController extends BaseController
{
    /**
     * List of feeds
     * @param Request $request the current request
     *
     * @return Response
     */
    public function loginAction(Request $request)
    {
        $json = json_decode($request->getContent());
        $userRepo = $this->em->getRepository('NPSModelBundle:User');
        $cache = $this->container->get('server_cache');

        if ($userRepo->checkLogged($cache, $json->appKey, $json->username)) {
            echo NotificationHelper::OK; exit();
        } else {
            $checkUser = $userRepo->checkLogin($json->username, $json->password);

            if ($checkUser instanceof User) {
                $deviceRepo = $this->em->getRepository('NPSModelBundle:Device');
                $deviceRepo->createDevice($json->appKey, $checkUser);

                $cache->set("device_".$json->appKey, $checkUser->getId());
                echo NotificationHelper::OK; exit();
            } else {
                echo $checkUser; exit();
            }
        }
    }

    /**
     * Sign up an user
     * @param Request $request
     */
    public function signUpAction(Request $request)
    {
        $json = json_decode($request->getContent());
        $userRepo = $this->em->getRepository('NPSModelBundle:User');
        $cache = $this->container->get('server_cache');

        $checkUser = $userRepo->checkUserExists($json->username, $json->email);
        if (empty($checkUser)) {
            $user = $userRepo->createUser($json->username, $json->email, $json->password);
            if ($user instanceof User) {
                $deviceRepo = $this->em->getRepository('NPSModelBundle:Device');
                $deviceRepo->createDevice($json->appKey, $user);

                $cache->set("device_".$json->appKey, $user->getId());
                echo NotificationHelper::OK; exit();
            }
            echo NotificationHelper::ERROR_TRY_LATER; exit();
        } else {
            echo $checkUser; exit();
        }
    }
}
