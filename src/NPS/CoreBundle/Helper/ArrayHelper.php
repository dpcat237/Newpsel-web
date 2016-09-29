<?php

namespace NPS\CoreBundle\Helper;

use Symfony\Component\Templating\Helper\Helper;

/**
 * Class for generic methods with array
 */
class ArrayHelper extends Helper
{
    /** @var string */
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
     * Split collection items in two array by key
     *
     * @param array  $collections
     * @param string $filterKey
     *
     * @return array: true, false
     */
    static public function splitArray(array $collections, $filterKey)
    {
        $trueItems  = array();
        $falseItems = array();

        foreach ($collections as $collectionKey => $collection) {
            if ($collectionKey == $filterKey) {
                $trueItems = $collection;
            } else {
                $falseItems = $collection;
            }
        }

        return array($trueItems, $falseItems);
    }

    /**
     * Separate ids to array
     *
     * @param array  $collection
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

    /**
     * Filter items which still unread in device
     *
     * @param array  $unreadItems unread items to send to api
     * @param array  $unreadIds   still unread items ids from api
     * @param string $idKey       id key
     *
     * @return array
     */
    static public function filterUnreadItemsIds($unreadItems, $unreadIds, $idKey = 'api_id')
    {
        foreach ($unreadItems as $key => $unreadItem) {
            if (in_array($unreadItem[$idKey], $unreadIds)) {
                unset($unreadItems[$key]);
            }
        }

        return $unreadItems;
    }
}
