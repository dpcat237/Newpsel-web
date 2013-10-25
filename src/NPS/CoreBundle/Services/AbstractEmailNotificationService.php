<?php
namespace NPS\CoreBundle\Services;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Bundle\TwigBundle\Debug\TimedTwigEngine;
use \Swift_Mailer;
use Doctrine\ORM\EntityManager;

abstract class AbstractEmailNotificationService
{
    private $templating;
    private $mailer;
    private $translator;

    /**
     * @var $entityManager EntityManager
     */
    private $entityManager;

    /**
     * All email notification services need this
     *
     * @param TimedTwigEngine $templating TimedTwigEngine
     * @param Swift_Mailer    $mailer     Swift_Mailer
     * @param Translator      $translator Translator
     */
    public function __construct(TimedTwigEngine $templating, Swift_Mailer $mailer, Translator $translator)
    {
        $this->templating = $templating;
        $this->mailer = $mailer;
        $this->translator = $translator;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }


    /**
     * @return mixed
     */
    public function getTemplating()
    {
        return $this->templating;
    }


    /**
     * @return mixed
     */
    public function getMailer()
    {
        return $this->mailer;
    }


    /**
     * @return mixed
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @return mixed
     */
    public function getTransporter()
    {
        $transporter = \Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, 'ssl')
            ->setUsername('newpsel@gmail.com')
            ->setPassword('n#06p04e2013r#s');

        return $transporter;
    }
}