<?php
namespace NPS\CoreBundle\Helper;

use Symfony\Component\Templating\Helper\Helper;

/**
 * Class for notifications
 */
class NotificationHelper extends Helper
{
    public $name = 'NotificationHelper';
    protected $objectName;
    protected $message;
    protected $messageType;

    /**
     * Constructor
     * @param string $objectName
     */
    public function __construct($objectName)
    {
        $this->setObjectName($objectName);
    }

    /**
     * Set object's name for messages
     * @param string $objectName
     */
    public function setObjectName($objectName)
    {
        $this->objectName = $objectName;
    }

    /**
     * Get object's name
     * @return string
     */
    public function getObjectName()
    {
        return $this->objectName;
    }

    /**
     * Set message text
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Get message
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set message type
     * @param string $messageType
     */
    public function setMessageType($messageType)
    {
        $this->messageType = $messageType;
    }

    /**
     * Get message
     * @return string
     */
    public function getMessageType()
    {
        return $this->messageType;
    }

    /**
     * Set message and message type for notification
     * @param integer $messageId
     */
    public function setNotification($messageId)
    {
        switch ($messageId){
            case 1:
                $this->setMessageType('success');
                $this->setMessage($this->getObjectName()."'s data saved successfully");
                break;
            case 201:
                $this->setMessageType('alert');
                $this->setMessage("Review the errors to save your data");
                break;
            case 301:
                $this->setMessageType('error');
                $this->setMessage("Try again after several minutes");
                break;
            case 302:
                $this->setMessageType('error');
                $this->setMessage("Feed's url is wrong");
                break;
            case 303:
                $this->setMessageType('error');
                $this->setMessage("Feed doesn't exists");
                break;
            default:
                $this->setMessageType('undefined');
                $this->setMessage("Undefined alert: ".$messageId);
                break;
        }
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName()
    {
        return $this->name;
    }
}
