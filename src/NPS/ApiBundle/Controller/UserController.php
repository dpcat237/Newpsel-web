<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use NPS\ApiBundle\Controller\BaseController;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Entity\Device;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * FeedController
 */
class UserController extends BaseController
{
    /**
     * Sign up an user
     * @param Request $request
     */
    public function signUpAction(Request $request)
    {
        $json = json_decode($request->getContent());
        $userRepo = $this->em->getRepository('NPSCoreBundle:User');
        $cache = $this->container->get('server_cache');

        $checkUser = $userRepo->checkUserExists($json->username, $json->email);
        if (empty($checkUser)) {
            $user = $userRepo->createUser($json->username, $json->email, $json->password);
            if ($user instanceof User) {
                $deviceRepo = $this->em->getRepository('NPSCoreBundle:Device');
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
