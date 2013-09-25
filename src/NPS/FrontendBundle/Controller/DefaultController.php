<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Entity\User;

/**
 * Class DefaultController
 *
 * @package NPS\FrontendBundle\Controller
 */
class DefaultController extends BaseController
{
    /**
     * Welcome page with sing in and sign up
     * @param Request $request
     *
     * @return Response
     * @Route("/", name="welcome")
     * @Template("NPSFrontendBundle:Welcome:index.html.twig")
     *
     */
    public function welcomeAction(Request $request)
    {
        if ($this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->container->get('router')->generate('homepage'));
        }

        return array();
    }

    /**
     * Homepage
     * @param Request $request
     *
     * @return Response
     * @Route("/home", name="homepage")
     */
    public function homeAction(Request $request)
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
        $userRepo = $this->em->getRepository('NPSCoreBundle:User');
        $userRepo->subscribeToNewsletter($request->get('email'));

        $response = array (
            'result' => NotificationHelper::OK
        );

        return new JsonResponse($response);
    }

    /**
     * Process a POST for a login
     * @param Request $request
     *
     * @return boolean
     */
    public function processLogin($request)
    {

        //if he's not logged
        $formData = $request->get('signIn');
        $username = $formData['username'];
        $password = $formData['password'];

        //check password
        $userRepo = $this->em->getRepository('NPSCoreBundle:User');
        $user = $userRepo->findOneByUsername($username);
        $ok = false;
        if ($user instanceof User) {
            $ok = ($user->getPassword() == sha1("sc_".$password));
        }

        //check that pwd is OK and user is enabled
        if ($ok && $user->isEnabled()) {
            //password is correct, make login
            $this->doLogin($user);

            return true;
        }

        //login is not correct, set AUTH_ERROR in context, display login page
        $request->attributes->set(SecurityContext::AUTHENTICATION_ERROR, true);

        return false;
    }

    /**
     * Action to manage a login attempt
     *
     * @param Request $request the current request
     *
     * @return Response
     *
     * @Route("/sign_in", name="sign_in")
     * @Template("NPSFrontendBundle:Default:sign_in.html.twig")
     */
    public function loginAction(Request $request)
    {
        $errors = null;
        //if user is logged redirect to homepage
        if ($this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->container->get('router')->generate('homepage'));
        }

        //if login process went well redirect to homepage
        if ($request->getMethod() == 'POST' && $this->processLogin($request)) {
            return new RedirectResponse($this->container->get('router')->generate('homepage'));
        }

        //check for login process errors
        if ($this->checkLoginErrors($request)) {
            $errors = NotificationHelper::ERROR_LOGIN_DATA;
        }

        $objectClass = 'NPS\CoreBundle\Entity\User';
        $objectTypeClass = 'NPS\FrontendBundle\Form\Type\SignInType';
        $form = $this->createFormEdit(null, 'User', $objectClass, $objectTypeClass);
        $viewData = array(
            'errors' => $errors,
            'form' => $form->createView()
        );

        return $viewData;
    }

    /**
     * Get the login error if there is one
     * @param $request
     *
     * @return mixed
     */
    private function checkLoginErrors($request)
    {
        $error = null;
        $session = $request->getSession();
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        return $error;
    }

    /**
     * Signup action
     * @param Request $request
     *
     * @return Response
     * @Route("/sign_up", name="sign_up")
     */
    public function signupAction(Request $request)
    {
        $errors = '';
        $objectClass = 'NPS\CoreBundle\Entity\User';
        $objectTypeClass = 'NPS\FrontendBundle\Form\Type\SignUpType';
        $form = $this->createFormEdit(null, 'User', $objectClass, $objectTypeClass);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            $user = $form->getData();

            if ($form->isValid()) {
                $userRepo = $this->em->getRepository('NPSCoreBundle:User');
                $errors = $userRepo->checkUserExists($user->getUsername(), $user->getEmail());
                if (empty($errors)) {
                    $password = sha1("sc_".$user->getPassword());
                    $user->setPassword($password);
                    $user->setIsEnabled(true);
                    $user->setRegistered(true);

                    $this->saveObject($user);
                    // Generate Activation Code
                    //$ac = array("userid" => $user->getId(), "activationcode" => sha1(microtime()));
                    // Set verification code key in cache
                    //$cache = $this->container->get('redis_cache');
                    //$cache->set("verify:".$ac["userid"]."_".$ac["activationcode"], "");
                    //  Show message 'check your email to confirm registration...'

                    return new RedirectResponse($this->container->get('router')->generate('sign_in'));
                } else {
                    $this->get('system_notification')->setMessage($errors);
                }
            } else {
                $this->get('system_notification')->setMessage(NotificationHelper::ALERT_FORM_DATA);
            }
        }

        $viewData = array (
            'form' => $form->createView(),
            'errors' => $errors
        );

        return $this->render('NPSFrontendBundle:Default:sign_up.html.twig', $viewData);
    }

    /**
     * Tries to put a user in the security context
     *
     * @param UserInterface $user The user to log in
     *
     * @return boolean ok if the login was successful (user was granted all roles)
     */
    public function doLogin(UserInterface $user)
    {
        //ok is true by default
        $ok = true;
        //invalidate current token
        $this->get('security.context')->setToken(null);
        $this->get('request')->getSession()->invalidate();
        // Only enabled users allowed. Set to true after activation code confirm e-mail.
        if ($user->isEnabled()) {
            //create new token
            $token = new UsernamePasswordToken($user, null, 'secured_area', $user->getRoles());
            $this->get('security.context')->setToken($token);
            //roles
            $roles = $user->getRoles();
            foreach ($roles as &$role) {
                //check that each role was granted correctly
                $ok = $ok && ($this->get('security.context')->isGranted($role));
            }
            if ($ok) {
                //serialize token and put it on session
                $this->get('request')->getSession()->set('_security_secured_area', serialize($token));
            }
        }

        return $ok;
    }

    /**
     * Logout
     * @param Request $request
     *
     * @return RedirectResponse
     * @Route("/logout", name="logout")
     */
    public function logoutAction(Request $request)
    {
        $request->getSession()->invalidate();

        return new RedirectResponse($this->container->get('router')->generate('welcome'));
    }

    /**
     * Test crawler
     *
     * @return RedirectResponse
     * @Route("/craw", name="craw")
     */
    public function tryCrawlerAction()
    {
        //return new RedirectResponse($this->container->get('router')->generate('homepage'));
        $link = 'http://www.ricardclau.com/feed';
        $artTitle = "Resumen Q1 2012 – Muchos cambios";
        $artUrl = 'http://www.ricardclau.com/2012/03/resumen-q1-2012-muchos-cambios/';
        $crawler = $this->get('try');

        //$crawler->showFeedItems($link);
        $crawler->tryCrawledItem($link, $artTitle, $artUrl);




        //TODO
        /* 4 ok
        http://feeds.mashable.com/Mashable
        $crawler = $client->request('GET', 'http://feeds.mashable.com/~r/Mashable/~3/8k8H54amJig/');
        $class = $crawler->filterXPath("//*[text()[contains(., 'At this point, inviting')]]");
        $test = $class->parents();*/

        /* 6 doesn't work because bold description at the beginning which aren't same level of article
        http://feeds.feedburner.com/androidcentral
        $crawler = $client->request('GET', 'http://feedproxy.google.com/~r/androidcentral/~3/QE3tl_hxqYw/story01.htm');
        $class = $crawler->filterXPath("//*[text()[contains(., 'At an event in Beijing this mornin')]]");
        $test = $class->parents();*/

        /* 7 doesn't work because of short origin text; remove top share and video objects (notify about video)
        http://feeds.bbci.co.uk/news/world/rss.xml
        $crawler = $client->request('GET', 'http://www.bbc.co.uk/news/world-asia-24201243#sa-ns_mchannel=rss&ns_source=PublicRSS20-sa');
        $class = $crawler->filterXPath("//*[text()[contains(., 'Many burials have taken place')]]");
        $test = $class->parents();*/

        // 8 http://www.eduardpunset.es/category/general/feed - review later

        /* 14 ok
        http://feeds.gawker.com/lifehacker/full
        $crawler = $client->request('GET', 'http://feeds.gawker.com/~r/lifehacker/full/~3/q4_KZFN_TqI/how-to-create-a-diet-plan-that-doesnt-suck-and-actuall-1352148537');
        $class = $crawler->filterXPath("//*[text()[contains(., 'probably heard of a million dietary')]]");
        $test = $class->parents();*/

        /* 15 ok; extract related posts and remove share
        http://feeds.feedburner.com/MarcAndAngel
        $crawler = $client->request('GET', 'http://www.marcandangel.com/2013/09/22/8-things-you-should-not-do-to-get-ahead/');
        $class = $crawler->filterXPath("//*[text()[contains(., 'So make that choice today.')]]");
        $test = $class->parents();*/

        /* 17  doesn't work generic
        http://feeds.newscientist.com/science-news
        $crawler = $client->request('GET', 'http://feeds.newscientist.com/c/749/f/10897/s/3199538f/sc/15/l/0L0Snewscientist0N0Carticle0Cdn242510Egps0Eantenna0Efilters0Eout0Enoise0Eto0Eboost0Eurban0Eaccuracy0Bhtml0Dcmpid0FRSS0QNSNS0Q20A120EGLOBAL0Qonline0Enews/story01.htm');
        $class = $crawler->filterXPath("//*[text()[contains(., 'For something we rely on so heavily')]]");
        $test = $class->parents();
        $tst = explode('<!-- social media btns -->', $test->html());
        $class = $crawler->filterXPath("//p[@class='infotext']");
        echo 'tut: '.$tst[0].$class->html(); exit();*/

        /* 19 doesn't work with generic filter
        http://www.antena3.com/rss/9.xml
        $crawler = $client->request('GET', 'http://www.antena3.com/noticias/mundo/angela-merkel-reelegida-canciller-resiste-fuerza-embiste-crisis-europa_2013092300104.html');
        $class = $crawler->filterXPath("//*[text()[contains(., 'Uno a uno han ido sufriendo duras derrotas')]]");
        $test = $class->parents();*/

        /* 20 doesn't work with generic
        http://feeds.feedburner.com/d0od
        $crawler = $client->request('GET', 'http://feedproxy.google.com/~r/d0od/~3/BZmODHC7dVY/valve-announce-steamos');
        $class = $crawler->filterXPath("//*[text()[contains(., 'being the heart of their plans for')]]");
        $test = $class->parents();*/

        /* 26 doesn't work generic
        http://mobile-review.com.feedsportal.com/c/33244/f/556830/index.rss
        $crawler = $client->request('GET', 'http://mobile-review.com.feedsportal.com/c/33244/f/556830/s/318f908b/sc/5/l/0L0Smobile0Ereview0N0Carticles0C20A130Cbirulki0E2430Bshtml/story01.htm');
        $class = $crawler->filterXPath("//*[text()[contains(., 'Продажи новых iPhone и LTE в России')]]");
        $test = $class->parents();
        $tst = explode('<center>', $test->html());
        $tst[0]*/

        /* 30 ok
        http://www.ricardclau.com/feed
        $crawler = $client->request('GET', 'http://www.ricardclau.com/2013/08/4-meses-en-londres-experiencias/');
        $class = $crawler->filterXPath("//*[text()[contains(., 'por lo menos no va a haber')]]");
        $test = $class->parents();*/
    }
}