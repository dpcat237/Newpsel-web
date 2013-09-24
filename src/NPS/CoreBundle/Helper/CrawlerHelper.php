<?php
namespace NPS\CoreBundle\Helper;

use Symfony\Component\Templating\Helper\Helper;

/**
 * Class for time functions
 */
class CrawlerHelper extends Helper
{
    public $name = 'CrawlerHelper';

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return $this->name;
    }

    static public function process1()
    {
        //TODO
        return 'oki aa';
    }
}
