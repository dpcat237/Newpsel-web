<?php

namespace NPS\CoreBundle\Services;

use Swift_SmtpTransport;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractEmailNotificationService
{
    /** @var TwigEngine */
    protected $templating;
    /** @var TranslatorInterface */
    protected $translator;
    /** @var $entityManager EntityManager */
    protected $entityManager;
    /** @var string */
    protected $emailSender = 'newpsel@gmail.com';

    /**
     * AbstractEmailNotificationService constructor.
     *
     * @param TwigEngine          $templating
     * @param TranslatorInterface $translator
     */
    public function __construct(TwigEngine $templating, TranslatorInterface $translator)
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
