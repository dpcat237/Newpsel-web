<?php

namespace NPS\FrontendBundle\Controller;

use NPS\FrontendBundle\Form\Type\ImportOpmlType;
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
        if ($this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->container->get('router')->generate('homepage'));
        }

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

    /**
     * Subscribe to newsletter
     *
     * @return Response
     * @Route("/preference/imp_exp", name="preference_imp_exp")
     * @Secure(roles="ROLE_USER")
     * @Template("NPSFrontendBundle:Preference:imp_exp.html.twig")
     */
    public function impExpAction()
    {
        $opmlType = new ImportOpmlType();
        $opmlForm = $this->createForm($opmlType);

        $pocketType = $this->get('nps.form.type.import.pocket');
        $pocketForm = $this->createForm($pocketType);

        $instapaperType = $this->get('nps.form.type.import.instapaper');
        $instapaperForm = $this->createForm($instapaperType);

        $viewData = array(
            'opml_form' => $opmlForm->createView(),
            'pocket_form' => $pocketForm->createView(),
            'instapaper_form' => $instapaperForm->createView(),
        );

        return $viewData;
    }
}