<?php
namespace NPS\CoreBundle\Services;

use Swift_Mailer;
use Swift_Message;

/**
 * Class UserNotificationsService
 *
 * @package NPS\CoreBundle\Services
 */
class UserNotificationsService extends AbstractEmailNotificationService
{
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

        $message = Swift_Message::newInstance()
            ->setSubject($this->translator->trans('_Chrome_key_subject'))
            ->setFrom($this->emailSender)
            ->setTo($userEmail)
            ->setBody($this->templating->render('NPSFrontendBundle:Email:chrome_key.html.twig', $viewData))
            ->setContentType('text/html');

        $mailer = Swift_Mailer::newInstance($this->getTransporter());
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

        $message = Swift_Message::newInstance()
            ->setSubject($this->translator->trans('_Verify_email_subject'))
            ->setFrom($this->emailSender)
            ->setTo($userEmail)
            ->setBody($this->templating->render('NPSFrontendBundle:Email:verify_email.html.twig', $viewData))
            ->setContentType('text/html');

        $mailer = Swift_Mailer::newInstance($this->getTransporter());
        $mailer->send($message);
    }

    /**
     * Send email with link to create new password
     *
     * @param string $userEmail
     * @param string $recoveryKey
     */
    public function sendPasswordRecovery($userEmail, $recoveryKey)
    {
        $viewData = array(
            'key' => $recoveryKey
        );

        $message = Swift_Message::newInstance()
            ->setSubject($this->translator->trans('_Password_recovery_subject'))
            ->setFrom($this->emailSender)
            ->setTo($userEmail)
            ->setBody($this->templating->render('NPSFrontendBundle:Email:password_recovery.html.twig', $viewData))
            ->setContentType('text/html');

        $mailer = Swift_Mailer::newInstance($this->getTransporter());
        $mailer->send($message);
    }
}