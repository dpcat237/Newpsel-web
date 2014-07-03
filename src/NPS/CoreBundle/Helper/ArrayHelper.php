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
     * Separate read and unread items
     *
     * @param array $items of read and unread items
     *
     * @return array two arrays
     */
    static public function separateUnreadArray($items)
    {
        $readItems = array();
        $unreadItems = array();

        foreach ($items as $item) {
            if ($item['is_unread']) {
                $unreadItems[] = $item;
            } else {
                $readItems[] = $item;
            }
        }

        return array($readItems, $unreadItems);
    }

    /**
     * Separate ids to array
     *
     * @param array $collection
     * @param string $key
     *
     * @return array
     */
    static public function getIdsFromArray($collection, $key = 'id')
    {
        $ids = array();
        foreach ($collection as $value) {
            $ids[] = $value[$key];
        }

        return $ids;
    }
}
