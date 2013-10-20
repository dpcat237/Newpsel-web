<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * Class DefaultController
 *
 * @package NPS\FrontendBundle\Controller
 */
class DefaultController extends BaseController
{
    /**
     * Welcome page with sing in and sign up
     *
     * @return Response
     * @Route("/", name="welcome")
     * @Template("NPSFrontendBundle:Welcome:index.html.twig")
     *
     */
    public function welcomeAction()
    {
        if ($this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->container->get('router')->generate('homepage'));
        }

        return array();
    }

    /**
     * Homepage
     *
     * @return Response
     * @Route("/home", name="homepage")
     */
    public function homeAction()
    {
        if ($this->get('security.context')->isGranted('ROLE_USER')) {
            $viewData = array();

            return $this->render('NPSFrontendBundle:Default:index.html.twig', $viewData);
        }

        return new RedirectResponse($this->container->get('router')->generate('welcome'));
    }

    /**
     * Subscribe to newsletter
     * @param Request $request
     *
     * @return Response
     * @Route("/subscribe", name="subscribe")
     */
    public function subscribeAction(Request $request)
    {
        $userRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:User');
        $userRepo->subscribeToNewsletter($request->get('email'));

        $response = array (
            'result' => NotificationHelper::OK
        );

        return new JsonResponse($response);
    }

    /**
     * Test crawler
     *
     * @return RedirectResponse
     * @Route("/craw", name="craw")
     *
     * to try:
     *
    $feedId = 14;
    $link = 'http://feeds.feedburner.com/MarcAndAngel';
    $artTitle = "8 Things You Should NOT Do to Get Ahead";
    $artUrl = 'http://feeds.gawker.com/~r/lifehacker/full/~3/eREZUL0Eto0/how-can-i-find-out-where-an-email-really-came-from-1190061668';
    $itemContent = "Content...";
    $crawler = $this->get('try');

    //print file_get_contents($artUrl); exit();

    //$crawler->showFeedItems($link);
    //$crawler->tryCrawledItem($link, $artTitle, $artUrl, $feedId);
    $crawler->tryDirectContent($artUrl, $itemContent, $feedId);
     */
    public function tryCrawlerAction()
    {
        return new RedirectResponse($this->container->get('router')->generate('homepage'));
    }
}