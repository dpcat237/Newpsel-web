<?php
namespace NPS\FrontendBundle\Services;

/**
 * SystemNotificationService
 */
class SystemNotificationService
{
    /**
     * Constructor
	 * @param object $session
     */
    public function __construct($session)
    {
        $this->session = $session;
    }

    /**
     * Get proper data end create flash message
     * @param $messageCode
     * @param null $type
     */
    public function setMessage($messageCode, $type = null)
    {
        $type = ($type)?: $this->getType(substr($messageCode, 0, 1));
        $message = (is_numeric($messageCode))? 'notification_'.$messageCode : $messageCode;
        $this->session->getFlashBag()->add($type, $message);
    }

    /**
     * Set type of message
     * @param $code
     *
     * @return string
     */
    private function getType($code){
        switch ($code){
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
}
