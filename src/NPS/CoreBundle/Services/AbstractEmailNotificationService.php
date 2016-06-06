<?php

namespace NPS\CoreBundle\Services;

use Swift_SmtpTransport;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractEmailNotificationService
{
    const TRANSPORTER_HOST = 'smtp.gmail.com';
    const TRANSPORTER_PORT = 465;
    const TRANSPORTER_SECURITY = 'ssl';

    /** @var TwigEngine */
    protected $templating;
    /** @var TranslatorInterface */
    protected $translator;
    /** @var string */
    protected $emailSender;
    /** @var string */
    protected $senderPassword;

    /**
     * AbstractEmailNotificationService constructor.
     *
     * @param TwigEngine          $templating
     * @param TranslatorInterface $translator
     * @param string              $emailSender
     * @param string              $senderPassword
     */
    public function __construct(TwigEngine $templating, TranslatorInterface $translator, $emailSender, $senderPassword)
    {
        $this->templating     = $templating;
        $this->translator     = $translator;
        $this->emailSender    = $emailSender;
        $this->senderPassword = $senderPassword;
    }

    /**
     * Get transporter
     *
     * @return Swift_SmtpTransport
     */
    protected function getTransporter()
    {
        $transporter = Swift_SmtpTransport::newInstance(self::TRANSPORTER_HOST, self::TRANSPORTER_PORT, self::TRANSPORTER_SECURITY)
            ->setUsername($this->emailSender)
            ->setPassword($this->senderPassword);

        return $transporter;
    }
}
