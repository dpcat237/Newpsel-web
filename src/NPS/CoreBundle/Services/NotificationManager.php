<?php
namespace NPS\CoreBundle\Services;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * NotificationManager
 */
class NotificationManager
{
    /** @var Logger */
    private $logger;
    /** @var Session */
    private $session;
    /** @var TranslatorInterface */
    private $translator;

    /**
     * NotificationManager constructor.
     *
     * @param Logger              $logger
     * @param Session             $session
     * @param TranslatorInterface $translator
     */
    public function __construct(Logger $logger, Session $session, TranslatorInterface $translator)
    {
        $this->logger     = $logger;
        $this->session    = $session;
        $this->translator = $translator;
    }

    /**
     * Get proper data end create flash message
     *
     * @param      $messageCode
     * @param null $type
     */
    public function setFlashMessage($messageCode, $type = null)
    {
        $type    = ($type) ?: $this->getType(substr($messageCode, 0, 1));
        $message = (is_numeric($messageCode)) ? 'notification_' . $messageCode : $messageCode;
        $this->session->getFlashBag()->add($type, $message);
    }

    /**
     * Set type of message
     *
     * @param string $code
     *
     * @return string
     */
    private function getType($code)
    {
        switch ($code) {
            case 1:
                $type = 'success';
                break;
            case 2:
                $type = 'alert';
                break;
            case 3:
                $type = 'error';
                break;
            default:
                $type = 'notification';
                break;
        }

        return $type;
    }

    /**
     * Set log
     *
     * @param string $message
     * @param string $type
     */
    public function setLog($message, $type = 'info')
    {
        $this->logger->$type($message);
    }

    /**
     * Translate
     *
     * @param string $string
     *
     * @return string
     */
    public function trans($string)
    {
        return $this->translator->trans($string);
    }
}
