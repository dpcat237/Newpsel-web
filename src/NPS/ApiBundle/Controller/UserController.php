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
        $json = json_decode($request->getContent());
        echo '<pre>tut: '; print_r($json); echo '</pre>'; exit();




        //echo 'tut: '.$request->getMethod();
        /*echo '<pre>tut1: '; print_r($_FILES); echo '</pre>';
        \Doctrine\Common\Util\Debug::dump($_POST);
        echo '<pre>tut2: '; print_r($request->getContent()); echo '</pre>';
        $json = $request->request->get('JSONFile');
        $jsonData = json_decode($json);
        echo '<pre>tut3: '; print_r($jsonData); echo '</pre>'; exit();*/
    }

}
