<?php

namespace NPS\FrontendBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use NPS\CoreBundle\Event\UserSignUpEvent;
use NPS\CoreBundle\NPSCoreEvents;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
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
     * @Route("/user/preference", name="user_preferences")
     * @Secure(roles="ROLE_USER")
     * @Template()
     *
     * @return Response
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
     * @param Form $form
     *
     * @return array
     */
    protected function processLoginForm(Form $form)
    {

        $email = $form->get('email')->getData();
        $password = $form->get('password')->getData();

        //check password
        $userRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:User');
        $user = $userRepo->findOneByEmail($email);
        $ok = false;
        if ($user instanceof User) {
            $ok = ($user->getPassword() == sha1($this->container->getParameter('nseck').'_'.$password));
        }

        if (!$ok) {
            $form->get('password')->addError(new FormError($this->get('translator')->trans('_Email_pwd_wrong')));

            return array(false, $form);
        }

        if ($ok && !$user->isEnabled()) {
            $form->get('password')->addError(new FormError($this->get('translator')->trans('_Verify_email')));
            $ok = false;
        }


        //check that pwd is OK and user is enabled
        if ($ok && $user->isEnabled()) {
            //password is correct, make login
            $this->doLogin($user);

            return array(true, $form);
        }

        return array(false, $form);
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
        if ($this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->container->get('router')->generate('homepage'));
        }

        $checkForm = false;
        $user = new User();
        $formType = new SignInType($user);
        $form = $this->createForm($formType, $user);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            list($checkForm, $form) = $this->processLoginForm($form);
        }

        if ($checkForm) {
            return new RedirectResponse($this->container->get('router')->generate('homepage'));
        }

        if ($request->getMethod() == 'POST' && !$checkForm) {
            //login is not correct, set AUTH_ERROR in context, display login page
            $request->attributes->set(SecurityContext::AUTHENTICATION_ERROR, true);
        }

        $viewData = array(
            'form' => $form->createView()
        );

        return $viewData;
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
        list($user, $errors) = $this->get('nps.entity.user')->saveFormUser($form, $this->container->getParameter('nseck'));
        if (!$errors) {

            $userSignUpEvent = new UserSignUpEvent($user);
            $this->get('event_dispatcher')->dispatch(NPSCoreEvents::USER_SIGN_UP, $userSignUpEvent);

            return new RedirectResponse($this->container->get('router')->generate('sign_in'));
        }
        $viewData = array (
            'form' => $form->createView(),
            'errors' => $errors
        );

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

    /**
     * Verify email
     *
     * @param Request $request
     *
     * @Route("/verify/email/{key}/", name="verify_email")
     *
     * @return RedirectResponse
     */
    public function verifyEmailAction(Request $request)
    {
        $user = $this->get('nps.entity.user')->getUserByVerifyCode($request->get('key'));
        if (!$user instanceof User) {
            return new RedirectResponse($this->container->get('router')->generate('welcome'));
        }
        $this->doLogin($user);

        return new RedirectResponse($this->container->get('router')->generate('homepage'));
    }
}