<?php
namespace NPS\CoreBundle\Services\Entity;

use Symfony\Component\Form\Form;
use NPS\CoreBundle\Entity\User,
    NPS\CoreBundle\Entity\Preference;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Services\Entity\AbstractEntityService;

/**
 * UserService
 */
class UserService extends AbstractEntityService
{
    /**
     * Save form of user preferences to data base
     * @param Form $form
     */
    public function saveFormPreferences(Form $form)
    {
        $formObject = $form->getData();
        if ($form->isValid() && $formObject instanceof Preference) {
            $this->saveObject($formObject, true);
        } else {
            $this->systemNotification->setMessage(NotificationHelper::ALERT_FORM_DATA);
        }
    }

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
            $user->setEnabled(true);
            $user->setRegistered(true);
            $this->saveObject($user, true);
        }
        $response[] = $user;
        $response[] = $check['errors'];

        return $response;
    }

    /**
     * Check user registration form
     * @param Form $form Form
     *
     * @return array
     */
    protected function checkFormUser(Form $form)
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

    /**
     * Set preference for new user
     *
     * @param User $user
     */
    public function setPreferenceNewUser(User $user)
    {
        $laterRepo = $this->doctrine->getRepository('NPSCoreBundle:Later');
        $laterRepo->createLabel($user, 'Read later');
        $laterRepo->createLabel($user, 'Watch later');
        $sharedLater = $laterRepo->createLabel($user, 'Shared');

        $preference = new Preference();
        $preference->setSharedLater($sharedLater);
        $this->entityManager->persist($preference);

        $user->setPreference($preference);
        $this->entityManager->flush();
    }
}