<?php
namespace NPS\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PageNotFoundController
 */
class PageNotFoundController extends Controller
{
    /**
     * Not found view
     *
     * @return array
     */
    public function viewAction()
    {
        return new Response(
            $this->renderView('NPSFrontendBundle:PageNotFound:view.html.twig'),
            404
        );
    }
}
