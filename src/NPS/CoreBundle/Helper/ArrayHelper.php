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
     * Separate ids to array
     *
     * @param array  $collection
     * @param string $key
     *
     * @return array
     */
    public static function getIdsFromArray(array $collection, $key = 'id')
    {
        $ids = [];
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
    public static function filterUnreadItemsIds($unreadItems, $unreadIds, $idKey = 'api_id')
    {
        foreach ($unreadItems as $key => $unreadItem) {
            if (in_array($unreadItem[$idKey], $unreadIds)) {
                unset($unreadItems[$key]);
            }
        }

        return $unreadItems;
    }

    /**
     * @param array $collection
     * @param string $key
     * @param string $value
     *
     * @return array
     */
    public static function joinValuesSameKey(array $collection, $key, $value)
    {
        if (!count($collection)) {
            return [];
        }

        foreach ($collection as $item) {
            $result[$item[$key]][] = $item[$value];
        }

        return $result;
    }

    /**
     * @param array $collection
     * @param string $key
     *
     * @return array
     */
    public static function moveContendUnderKey(array $collection, $key = 'id')
    {
        if (!count($collection)) {
            return [];
        }

        foreach ($collection as $item) {
            $result[$item[$key]] = $item;
        }

        return $result;
    }

    /**
     * @param array $collection
     * @param string $key
     *
     * @return array
     */
    public static function moveContendUnderRepetitiveKey(array $collection, $key = 'id')
    {
        if (!count($collection)) {
            return [];
        }

        foreach ($collection as $item) {
            $result[$item[$key]][] = $item;
        }

        return $result;
    }

    /**
     * Separate collection items in two array by boolean
     *
     * @param array  $collection
     * @param string $boolean
     *
     * @return array: true, false
     */
    public static function separateBooleanArray(array $collection, $boolean)
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
}
