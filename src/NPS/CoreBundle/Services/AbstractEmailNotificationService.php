<?php
namespace NPS\CoreBundle\Services;

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
     * @param $templating
     * @param $mailer
     * @param $translator
     */
    public function __construct($templating, $mailer, $translator)
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