<?php

namespace NPS\FrontendBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use NPS\CoreBundle\Event\UserSignUpEvent;
use NPS\CoreBundle\NPSCoreEvents;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken,
    Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\FrontendBundle\Form\Type\PreferenceEditType,
    NPS\FrontendBundle\Form\Type\SignInType,
    NPS\FrontendBundle\Form\Type\SignUpType;

/**
 * Class UserController
 *
 * @package NPS\FrontendBundle\Controller
 */
class UserController extends Controller
{
    /**
     * Edit user's preferences
     *
     * @param Request $request the current request
     *
     * @return Response
     *
     * @Route("/user/preference", name="user_preferences")
     * @Secure(roles="ROLE_USER")
     * @Template()
     */
    public function editPreferencesAction(Request $request)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $route = $this->container->get('router')->generate('user_preferences');
        $preference = $user->getPreference();
        $labelsQuery = $this->get('nps.entity.later')->getUserLabelsQuery();
        $formType = new PreferenceEditType($labelsQuery);
        $form = $this->createForm($formType, $preference);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            $this->get('nps.entity.user')->saveFormPreferences($form);

            return new RedirectResponse($route);
        }

        $viewData = array(
            'title' => 'Edit user settings',
            'form' => $form->createView(),
            'label' => $preference,
        );

        return $viewData;
    }


    /**
     * Process a POST for a login
     *
     * @param Request $request
     *
     * @return boolean
     */
    protected function processLogin(Request $request)
    {

        //if he's not logged
        $formData = $request->get('signIn');
        $username = $formData['username'];
        $password = $formData['password'];

        //check password
        $userRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:User');
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
     * @Template("NPSFrontendBundle:User:sign_in.html.twig")
     * Routing is defined in routing.yml
     *
     * @return Response
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

        return $this->prepareLoginViewData($errors);
    }

    /**
     * Prepare view data for login action
     * @param $errors
     *
     * @return array
     */
    protected function prepareLoginViewData($errors)
    {
        $user = new User();
        $formType = new SignInType($user);
        $form = $this->createForm($formType, $user);
        $viewData = array(
            'errors' => $errors,
            'form' => $form->createView()
        );

        return $viewData;
    }

    /**
     * Get the login error if there is one
     * @param Request $request Request
     *
     * @return mixed
     */
    private function checkLoginErrors(Request $request)
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
     * Sign up action
     *
     * @param Request $request
     *
     * @Template("NPSFrontendBundle:User:sign_up.html.twig")
     * Routing is defined in routing.yml
     *
     * @return Response|RedirectResponse
     */
    public function signupAction(Request $request)
    {
        $errors = false;
        $user = new User();
        $formType = new SignUpType($user);
        $form = $this->createForm($formType, $user);

        $viewData = array (
            'form' => $form->createView(),
            'errors' => $errors
        );

        if ($request->getMethod() != 'POST') {
            return $viewData;
        }

        $form->handleRequest($request);
        list($user, $errors) = $this->get('nps.entity.user')->saveFormUser($form);
        if (!$errors) {
            $userSignUpEvent = new UserSignUpEvent($user);
            $this->get('event_dispatcher')->dispatch(NPSCoreEvents::USER_SIGN_UP, $userSignUpEvent);

            return new RedirectResponse($this->container->get('router')->generate('sign_in'));
        }
        $viewData['errors'] = $errors;

        return $viewData;
    }

    /**
     * Tries to put a user in the security context
     *
     * @param User $user The user to log in
     *
     * @return boolean ok if the login was successful (user was granted all roles)
     */
    protected function doLogin(User $user)
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

    public function twitterCallbackAction()
    {
        echo 'tut: twitterCallbackAction'; exit;
    }
}