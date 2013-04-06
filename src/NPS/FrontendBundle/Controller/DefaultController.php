<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        echo 'tut: '; exit();
        return $this->render('NPSFrontendBundle:Default:index.html.twig', array('name' => $name));
    }
}
