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
    const OK = 100;
    const OK_IS_READ = 110;
    const OK_IS_UNREAD = 111;
    const OK_IS_STARED = 112;
    const OK_IS_NOT_STARED = 113;
    /*Warning*/
    const WARNING = 200;
    /*Error*/
    const ERROR = 300;
    const ERROR_LOGIN_DATA = 301;
    const ERROR_NO_APP_KEY = 302;
    const ERROR_NO_LOGGED = 303;
    const ERROR_EMAIL_EXISTS = 305;
    const ERROR_WRONG_FEED = 306;
    const ERROR_NO_DATA = 307;
    const ERROR_TRY_LATER = 310;

    /**FRONTEND NOTIFICATIONS**/
    /*Success*/
    const SAVED_OK = 101;
    /*Notification*/
    /*Notice*/
    /*Alert*/
    const ALERT_FORM_DATA = 401;
    const ALERT_FEED_UPDATE_NOT_NEEDED = 402;
    /*Error*/
    const ERROR_TRY_AGAIN = 501;

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
