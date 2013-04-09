<?php
namespace NPS\CoreBundle\Helper;

/**
 * Class for time functions
 */
class DisplayHelper
{
    public $name = 'DisplayHelper';

    /**
     * Convert timestamp to human date
     * @param integer $timestamp [description]
     * @param string  $format    [description]
     *
     * @return date
     */
    public static function displayDate($timestamp, $format = 'd/m/Y H:i:s')
    {
        $humanDate = new \DateTime();
        $humanDate->setTimestamp($timestamp);

        return $humanDate->format($format);
    }
}
