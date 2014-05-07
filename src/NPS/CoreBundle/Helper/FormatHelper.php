<?php
namespace NPS\CoreBundle\Helper;

use DateTime;

/**
 * Class for time functions
 */
class FormatHelper
{
    public $name = 'FormatHelper';

    /**
     * Convert timestamp to human date
     *
     * @param integer $timestamp timestamp
     * @param string  $format    required format of date
     *
     * @return date
     */
    public static function displayDate($timestamp, $format = 'd/m/Y H:i:s')
    {
        $humanDate = new DateTime();
        $humanDate->setTimestamp($timestamp);

        return $humanDate->format($format);
    }

    /**
     * Get only lowercase language code from
     *
     * @param string $string language code
     *
     * @return string
     */
    public static function getLanguageCode($string)
    {
        if (strlen($string) < 2) {
            return "";
        }

        if (strlen($string) == 2) {
            return strtolower($string);
        }

        if (strpos($string,'-') !== false) {
            $string = explode('-', $string);

            return strtolower($string[0]);
        }

        return $string;
    }
}
