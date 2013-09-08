<?php
namespace NPS\CoreBundle\Services;

/**
 * Class UserNotificationsService
 * @package NPS\CoreBundle\Services
 */
class UserNotificationsService extends AbstractEmailNotificationService
{
    /**
     * Send new extension key to user
     * @param User   $user
     * @param string $extensionKey
     */
    public function sendChromeKey($user, $extensionKey)
    {
        $viewData = array(
            'user' => $user,
            'key' => $extensionKey
        );

        $message = \Swift_Message::newInstance()
            ->setSubject("Newpsel: Chrome extension key")
            ->setFrom('newpsel@gmail.com')
            ->setTo($user->getEmail())
            ->setBody($this->getTemplating()->render('NPSApiBundle:Email:chrome_key.html.twig', $viewData))
            ->setContentType('text/html');

        $mailer = \Swift_Mailer::newInstance($this->getTransporter());
        $mailer->send($message);
    }

}