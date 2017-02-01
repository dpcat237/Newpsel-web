<?php

namespace NPS\ApiBundle\DataTransformer;

/**
 * Class SavedItemTransformer
 *
 * @package NPS\ApiBundle\DataTransformer
 */
class SavedItemTransformer
{
    /**
     * @param array $savedItem
     * @param array $relatedItemsTags
     *
     * @return mixed
     */
    protected function transform($savedItem, $relatedItemsTags)
    {
        if (!$savedItem['language']) {
            $savedItem['language'] = $savedItem['item_language'];
        }
        $savedItem['tags'] = $relatedItemsTags[$savedItem['ui_id']];

        return $savedItem;
    }

    /**
     * @param array $savedItems
     * @param array $relatedItemsTags
     *
     * @return array
     */
    public static function transformList($savedItems, $relatedItemsTags)
    {
        if (!count($savedItems)) {
            return [];
        }

        foreach ($savedItems as $savedItem) {
            $result[] = self::transform($savedItem, $relatedItemsTags);
        }

        return $result;
    }
}
