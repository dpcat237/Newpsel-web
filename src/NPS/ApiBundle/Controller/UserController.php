<?php

namespace NPS\ApiBundle\Controller;

use NPS\ApiBundle\Services\Entity\DeviceApiService;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Services\Entity\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * Class UserController
 *
 * @package NPS\ApiBundle\Controller
 */
class UserController extends ApiController
{
    /**
     * User login
     *
     * @Rest\Post("/login")
     * @Rest\View
     *
     * @param Request $request the current request
     *
     * @return Response
     */
    public function loginAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $this->getDeviceService()->loginApi($this->getDeviceId($request), $json['email'], $json['password']);
    }

    /**
     * Register an user
     *
     * @Rest\Post("/register")
     * @Rest\View
     *
     * @param Request $request
     *
     * @return Response
     */
    public function registerAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $this->getDeviceService()->registerUserDevice($this->getDeviceId($request), $json['email'], $json['password']);
    }

    /**
     * Request password recovery
     *
     * @Rest\Post("/password_recovery")
     * @Rest\View
     *
     * @param Request $request
     *
     * @return Response
     */
    public function recoveryPasswordAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $this->getUserService()->requestRecoverPassword($json['email']);
    }

    /**
     * Register device without user data for preview
     *
     * @Rest\Post("/preview")
     * @Rest\View
     *
     * @param Request $request
     *
     * @return Response
     */
    public function previewAction(Request $request)
    {
        $this->getDeviceService()->registerPreviewDevice($this->getDeviceId($request));
    }

    /**
     * Register user data for preview user
     *
     * @Rest\Post("/preview/register")
     * @Rest\View
     *
     * @param Request $request
     *
     * @return Response
     */
    public function registerPreviewAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $this->getDeviceService()->addPreviewUserData($this->getDeviceId($request), $json['email'], $json['password']);
    }

    /**
     * @return DeviceApiService
     */
    protected function getDeviceService()
    {
        return $this->get('api.device.service');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->get('nps.entity.user');
    }
}
