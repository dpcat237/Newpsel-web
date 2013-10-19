<?php
namespace NPS\CoreBundle\Helper;

use Symfony\Component\Templating\Helper\Helper;

/**
 * Class for notifications
 */
class NotificationHelper extends Helper
{
    public $name = 'NotificationHelper';

    /**API NOTIFICATIONS**/
    /*Success*/
    CONST OK = 100;
    CONST OK_IS_READ = 110;
    CONST OK_IS_UNREAD = 111;
    CONST OK_IS_STARED = 112;
    CONST OK_IS_NOT_STARED = 113;
    /*Warning*/
    CONST WARNING = 200;
    /*Error*/
    CONST ERROR = 300;
    CONST ERROR_LOGIN_DATA = 301;
    CONST ERROR_NO_APP_KEY = 302;
    CONST ERROR_NO_LOGGED = 303;
    CONST ERROR_USERNAME_EXISTS = 304;
    CONST ERROR_EMAIL_EXISTS = 305;
    CONST ERROR_WRONG_FEED = 306;
    CONST ERROR_NO_DATA = 307;
    CONST ERROR_TRY_LATER = 310;

    /**FRONTEND NOTIFICATIONS**/
    /*Success*/
    CONST SAVED_OK = 101;
    /*Notification*/
    /*Notice*/
    /*Alert*/
    CONST ALERT_FORM_DATA = 401;
    /*Error*/
    CONST ERROR_TRY_AGAIN = 501;

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName()
    {
        return 'NotificationHelper';
    }
}
