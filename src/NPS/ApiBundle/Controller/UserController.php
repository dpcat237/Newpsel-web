<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use NPS\ApiBundle\Controller\BaseController;
use NPS\ModelBundle\Entity\User;

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
        $checkKey = $this->checkLogged($json->appKey, $json->username);

        if ($checkKey) {
            die("true");
        } else {
            $checkUser = $this->checkLogin($json->username, $json->password);

            if ($checkUser instanceof User) {
                $deviceRepo = $this->em->getRepository('NPSModelBundle:Device');
                $deviceRepo->addDevice('device_test', $checkUser);

                $cache = $this->container->get('server_cache');
                $cache->set("device_".$json->appKey, $checkUser->getId());
                die("true");
            } else {
                die('false');
            }
        }
    }

    private function checkLogin($username, $password){
        $userRepo = $this->em->getRepository('NPSModelBundle:User');
        $user = $userRepo->findOneByUsername($username);

        if ($user instanceof User) {
            $appKey = sha1("checkPwd_".$user->getPassword());

            if ($password == $appKey) {
                return $user;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private function checkLogged($appKey, $username = null)
    {
        $cache = $this->container->get('server_cache');
        $key = $cache->get($appKey);
        if ($key && $username) {
            $deviceRepo = $this->em->getRepository('NPSModelBundle:Device');
            $device = $deviceRepo->findOneByAppKey($appKey);
            if ($username == $device->getUser()->getIsername()) {
                return true;
            } else {
                return false;
            }
        } elseif ($key) {
            return true;
        }

        return false;
    }

}
