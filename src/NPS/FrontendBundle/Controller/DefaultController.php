<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use NPS\ModelBundle\Entity\User;

/**
 * Class DefaultController
 *
 * @package NPS\FrontendBundle\Controller
 */
class DefaultController extends BaseController
{
    /**
     * Home page
     * @return mixed
     */
    public function indexAction()
    {
        $name = ':)';

        return $this->render('NPSFrontendBundle:Default:index.html.twig', array('name' => $name));
    }

    /**
     * Login check action. Defined in routing.yml, only accepts GET
     *
     * @param Request $request the current request
     *
     * @return Response
     */
    public function loginCheckAction(Request $request)
    {
        if ($this->get('security.context')->isGranted('ROLE_ADMIN')) {
            //if the user is logged, go to the homepage
            return new RedirectResponse($this->container->get('router')->generate('homepage'));
        }
        //if user is not logged, go to admin login
        return new RedirectResponse($this->container->get('router')->generate('login'));
    }

    /**
     * Action to manage a login attempt
     *
     * @param Request $request the current request
     *
     * @return Response
     */
    public function loginAction(Request $request)
    {
        if ($this->get('security.context')->isGranted('ROLE_ADMIN')) {
            //if the user is logged, go to the homepage
            $redirectRoute = $this->container->get('router')->generate('homepage');

            return new RedirectResponse($redirectRoute);
        }
        $session = $request->getSession();

        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        if (!empty($error)) {
            // Get error list
            $errors = array(
                'hasErrors' => true,
                'password' => 'Username and password combination is not good'
            );
            // Send the error list to javascript
            return new Response(json_encode($errors));
        }

        $form = $this->createFormBuilder()
            ->add('username', null, array('required' => true))
            ->add('password', 'password', array('required' => true))
            ->getForm();
        $viewVars = array('error' => $error, 'form' => $form->createView());

        return $this->render('NPSFrontendBundle:Default:login.html.twig', $viewVars);
    }

    /**
     * Signup action, GET
     *
     * @param Request $request the current request
     *
     * @return Response
     */
    public function signupAction(Request $request)
    {
        $form = $this->_getSignupForm();

        return $this->render('NPSFrontendBundle:Default:signup.html.twig', array('form' => $form->createView()));
    }

    /**
     * Return form for signup
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     *
     * @return \Symfony\Component\Form\Form
     */
    private function _getSignupForm(UserInterface $user = null)
    {
        if (is_null($user)) {
            $user = new User();
        }

        return $this->createFormBuilder($user)
            ->add('username', 'text', array('label' => 'Your username'))
            ->add('email', 'text', array('label' => 'Your email'))
            ->add('password', 'repeated', array('type' => 'password',
                    'invalid_message' => "The passwords do not match",
                    'first_name' => 'password',
                    'first_options' => array(
                        'label' => 'Your Password' // Custom label for element
                    )
                )
            )
            ->add('isEnabled', 'hidden', array('data' => 1))
            ->getForm();
    }

    /**
     * Signup action, POST
     *
     * @param Request $request the current request
     *
     * @return Response
     */
    public function signupProcessAction(Request $request)
    {
        $form = $this->_getSignupForm();
        $form->bind($request);
        $user = $form->getData();
        // Set encrypted password
        $encoder = $this->container->get('security.encoder.blowfish');
        $password = $encoder->encodePassword($user->getPassword());
        $user->setPassword($password);
        $user->setIsEnabled(true);
        if ($form->isValid()) {
            $this->em = $this->getDoctrine()->getManager();
            $this->em->persist($user);
            $this->em->flush();
            // Generate Activation Code
            $ac = array("userid" => $user->getId(), "activationcode" => sha1(microtime()));
            // Set verification code key in cache
            $cache = $this->container->get('redis_cache');
            $cache->set("verify:".$ac["userid"]."_".$ac["activationcode"], "");
            //  Show message 'check your email to confirm registration...'
            $viewVars = array('Name' => $user->getUsername());

            return $this->render('NPSFrontendBundle:Default:signupok.html.twig', $viewVars);
        }

        return $this->render('NPSFrontendBundle:Default:signup.html.twig', array('form' => $form->createView()));
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
        if ($user->getIsEnabled()) {
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
}
