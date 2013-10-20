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
 * Class UserController
 *
 * @package NPS\FrontendBundle\Controller
 */
class UserController extends BaseController
{
    /**
     * Process a POST for a login
     * @param Request $request
     *
     * @return boolean
     */
    protected function processLogin($request)
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
     * @return Response
     *
     * @Route("/sign_in", name="sign_in")
     * @Template("NPSFrontendBundle:User:sign_in.html.twig")
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
     * @return Response|RedirectResponse
     *
     * @Route("/sign_up", name="sign_up")
     * @Template("NPSFrontendBundle:User:sign_up.html.twig")
     */
    public function signupAction(Request $request)
    {
        $errors = false;
        $objectClass = 'NPS\CoreBundle\Entity\User';
        $objectTypeClass = 'NPS\FrontendBundle\Form\Type\SignUpType';
        $form = $this->createFormEdit(null, 'User', $objectClass, $objectTypeClass);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            $errors = $this->get('user')->saveFormUser($form);
            if (!$errors) {
                return new RedirectResponse($this->container->get('router')->generate('sign_in'));
            }
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
     * @param UserInterface $user The user to log in
     *
     * @return boolean ok if the login was successful (user was granted all roles)
     */
    protected function doLogin(UserInterface $user)
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
}