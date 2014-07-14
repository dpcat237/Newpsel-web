<?php
namespace NPS\CoreBundle\Helper;

use Symfony\Component\Templating\Helper\Helper;
use NPS\CoreBundle\Services\CrawlerService;

/**
 * Class for generic methods with array
 */
class ArrayHelper extends Helper
{
    /**
     * @var string
     */
    public $name = 'ArrayHelper';


    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Separate collection items in two array by boolean
     *
     * @param array  $collection
     * @param string $boolean
     *
     * @return array: true, false
     */
    static public function separateBooleanArray(array $collection, $boolean)
    {
        $trueItems = array();
        $falseItems = array();

        foreach ($collection as $item) {
            if ($item[$boolean]) {
                $trueItems[] = $item;
            } else {
                $falseItems[] = $item;
            }
        }

        return array($trueItems, $falseItems);
    }

    /**
     * Separate ids to array
     *
     * @param array $collection
     * @param string $key
     *
     * @return array
     */
    static public function getIdsFromArray(array $collection, $key = 'id')
    {
        $ids = array();
        foreach ($collection as $value) {
            $ids[] = $value[$key];
        }

        return $ids;
    }
}
