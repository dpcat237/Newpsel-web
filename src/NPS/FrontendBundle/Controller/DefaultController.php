<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use NPS\CoreBundle\Helper\NotificationHelper;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class DefaultController
 *
 * @package NPS\FrontendBundle\Controller
 */
class DefaultController extends Controller
{
    /**
     * Welcome page with sing in and sign up
     *
     * @Template("NPSFrontendBundle:Welcome:index.html.twig")
     * Routing is defined in routing.yml
     *
     * @return Response
     */
    public function indexAction()
    {
        if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->container->get('router')->generate('homepage'));
        }

        return array();
    }

    /**
     * Privacy policy page
     *
     * @Template("NPSFrontendBundle:Welcome:privacyPolicy.html.twig")
     * Routing is defined in routing.yml
     *
     * @return Response
     */
    public function privacyPolicyAction()
    {
        return array();
    }

    /**
     * Homepage
     *
     * @return Response
     *
     * @Route("/home", name="homepage")
     * @Secure(roles="ROLE_USER")
     * @Template("NPSFrontendBundle:Default:index.html.twig")
     */
    public function homeAction()
    {
        /*$user = $this->getUser();
        $itemService = $this->get('api.item.service');
        $cooo = $itemService->getUnreadItems($user->getId(), [], 40);*/



        echo 'tut: h ok'; exit;


        return array();
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
}
