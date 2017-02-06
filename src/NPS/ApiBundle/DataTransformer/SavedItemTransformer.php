<?php

namespace NPS\ApiBundle\DataTransformer;

use NPS\CoreBundle\Helper\ArrayHelper;

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
    protected static function transform($savedItem, $relatedItemsTags)
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

        $result = [];
        foreach ($savedItems as $savedItem) {
            $result[] = self::transform($savedItem, $relatedItemsTags);
        }

        return $result;
    }

    /**
     * @param array $relatedItemsTags
     *
     * @return array
     */
    public static function transformListRelation($relatedItemsTags)
    {
        if (!count($relatedItemsTags)) {
            return [];
        }

        $result = [];
        $relatedItemsTags = ArrayHelper::moveContendUnderRepetitiveKey($relatedItemsTags, 'ui_id');
        foreach ($relatedItemsTags as $uiId => $itemData) {
            $result[] = [
                'ui_id' => $uiId,
                'tags' => ArrayHelper::getIdsFromArray($itemData, 'tag_id')
            ];
        }

        return $result;
    }
}
