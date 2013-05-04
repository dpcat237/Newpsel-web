<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use NPS\ApiBundle\Controller\BaseController;

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
        $json = $request->request->get('JSONFile');
        $jsonData = json_decode($json);
        echo '<pre>tut: '; print_r($jsonData); echo '</pre>'; exit();
    }

}
