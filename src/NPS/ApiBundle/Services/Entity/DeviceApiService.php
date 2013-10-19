<?php
namespace NPS\ApiBundle\Services\Entity;

use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Entity\User;

/**
 * DeviceApiService
 */
class DeviceApiService
{
    /**
     * @var $doctrine Doctrine
     */
    private $doctrine;

    /**
     * @var $secure SecureService
     */
    private $secure;


    /**
     * @param Doctrine      $doctrine    Doctrine
     * @param SecureService $secure      SecureService
     */
    public function __construct($doctrine, SecureService $secure)
    {
        $this->doctrine = $doctrine;
        $this->secure = $secure;
    }

    /**
     * Do login for Chrome Api
     * @param $appKey
     *
     * @return array
     */
    public function loginChromeApi($appKey)
    {
        $response = false;

        $user = $this->secure->getUserByDevice($appKey);
        if ($user instanceof User) {
            $response = true;
        }
        $responseData = array(
            'response' => $response
        );

        return $responseData;
    }

    /**
     * Send app key
     * @param $username
     *
     * @return array
     */
    public function requestAppKey($username)
    {
        $response = false;
        $userRepo = $this->doctrine->getRepository('NPSCoreBundle:User');
        $user = $userRepo->findOneByUsername($username);
        if($user instanceof User){
            $deviceRepo = $this->doctrine->getRepository('NPSCoreBundle:Device');
            $extensionKey = substr(hash("sha1", uniqid(rand(), true)), 0, 16);

            //send email to user with new key
            $this->userNotify->sendChromeKey($user, $extensionKey);
            //save new key for extension
            $deviceRepo->createDevice($extensionKey, $user);

            $response = true;
        }
        $responseData = array(
            'response' => $response,
        );

        return $responseData;
    }
}
