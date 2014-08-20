<?php
namespace NPS\CoreBundle\Services;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Swift_SmtpTransport;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\TwigBundle\TwigEngine;

abstract class AbstractEmailNotificationService
{
    /**
     * @var TwigEngine
     */
    protected $templating;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var $entityManager EntityManager
     */
    protected $entityManager;

    /**
     * @var string
     */
    protected $emailSender = 'newpsel@gmail.com';


    /**
     * All email notification services need this
     *
     * @param TwigEngine   $templating TwigEngine
     * @param Translator   $translator Translator
     */
    public function __construct(TwigEngine $templating, Translator $translator)
    {
        $this->templating = $templating;
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
    public function getTransporter()
    {
        $transporter = Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, 'ssl')
            ->setUsername($this->emailSender)
            ->setPassword('n#06p04e2013r#s');

        return $transporter;
    }
}