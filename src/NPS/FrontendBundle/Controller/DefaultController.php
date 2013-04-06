<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class DefaultController
 *
 * @package NPS\FrontendBundle\Controller
 */
class DefaultController extends Controller
{
    /**
     * Home page
     * @return mixed
     */
    public function indexAction()
    {
        echo 'tut: '; exit();

        return $this->render('NPSFrontendBundle:Default:index.html.twig', array('name' => $name));
    }
}
