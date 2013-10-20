<?php
namespace NPS\CoreBundle\Services\Entity;

use Symfony\Component\Form\Form;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Services\Entity\AbstractEntityService;

/**
 * UserService
 */
class UserService extends AbstractEntityService
{
    /**
     * Save form of user to data base
     * @param Form $form
     *
     * Add:
     * Generate Activation Code
     * $ac = array("userid" => $user->getId(), "activationcode" => sha1(microtime()));
     * Set verification code key in cache
     * $cache = $this->container->get('redis_cache');
     * $cache->set("verify:".$ac["userid"]."_".$ac["activationcode"], "");
     * Show message 'check your email to confirm registration...'
     */
    public function saveFormUser(Form $form)
    {
        $check = $this->checkFormUser($form);
        if (!$check['errors']) {
            $user = $check['user'];
            $password = sha1("sc_".$user->getPassword());
            $user->setPassword($password);
            $user->setIsEnabled(true);
            $user->setRegistered(true);

            $this->saveObject($user, true);
        }

        return $check['errors'];
    }

    /**
     * Check user registration form
     * @param $form
     *
     * @return array
     */
    protected function checkFormUser($form)
    {
        $errors = false;
        $user = $form->getData();
        if (!$form->isValid() || !$user instanceof User) {
            $this->systemNotification->setMessage(NotificationHelper::ALERT_FORM_DATA);
            $errors = true;
        }

        if (empty($errors)) {
            $userRepo = $this->doctrine->getRepository('NPSCoreBundle:User');
            $errors = $userRepo->checkUserExists($user->getUsername(), $user->getEmail());
        }

        $response = array(
            'errors' => $errors,
            'user'   => $user
        );

        return $response;
    }
}
