<?php
namespace NPS\CoreBundle\Services;

/**
 * Class UserNotificationsService
 *
 * @package NPS\CoreBundle\Services
 */
class UserNotificationsService extends AbstractEmailNotificationService
{
    /**
     * @var string
     */
    private $emailSender = 'newpsel@gmail.com';

    /**
     * Send new extension key to user
     *
     * @param string $userEmail
     * @param string $extensionKey extension key
     */
    public function sendChromeKey($userEmail, $extensionKey)
    {
        $viewData = array(
            'key' => $extensionKey
        );

        $message = \Swift_Message::newInstance()
            ->setSubject($this->getTranslator()->trans('_Chrome_key_subject'))
            ->setFrom($this->emailSender)
            ->setTo($userEmail)
            ->setBody($this->getTemplating()->render('NPSFrontendBundle:Email:chrome_key.html.twig', $viewData))
            ->setContentType('text/html');

        $mailer = \Swift_Mailer::newInstance($this->getTransporter());
        $mailer->send($message);
    }

    /**
     * Send welcome with link to email verification
     *
     * @param string $userEmail
     * @param string $activationKey
     */
    public function sendEmailVerification($userEmail, $activationKey)
    {
        $viewData = array(
            'key' => $activationKey
        );

        $message = \Swift_Message::newInstance()
            ->setSubject($this->getTranslator()->trans('_Verify_email_subject'))
            ->setFrom($this->emailSender)
            ->setTo($userEmail)
            ->setBody($this->getTemplating()->render('NPSFrontendBundle:Email:verify_email.html.twig', $viewData))
            ->setContentType('text/html');

        $mailer = \Swift_Mailer::newInstance($this->getTransporter());
        $mailer->send($message);
    }
}