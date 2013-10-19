<?php
namespace NPS\ApiBundle\Services;

use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Services\UserNotificationsService;
use NPS\CoreBundle\Entity\User;

/**
 * ChromeDataService
 */
class ChromeDataService
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
     * @var $userNotify UserNotificationsService
     */
    private $userNotify;


    /**
     * @param Doctrine                 $doctrine   Doctrine
     * @param SecureService            $secure     SecureService
     * @param UserNotificationsService $userNotify UserNotificationsService
     */
    public function __construct($doctrine, SecureService $secure, UserNotificationsService $userNotify)
    {
        $this->doctrine = $doctrine;
        $this->secure = $secure;
        $this->userNotify = $userNotify;
    }

    /**
     * Get user labels from app key
     * @param $appKey
     *
     * @return array
     */
    public function getUserLabels($appKey)
    {
        $response = false;
        $labels = array();
        $user = $this->secure->getUserByDevice($appKey);
        if ($user instanceof User) {
            $labelRepo = $this->doctrine->getRepository('NPSCoreBundle:Later');
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
        $responseData = array(
            'response' => $response,
            'labels' => $labels
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
