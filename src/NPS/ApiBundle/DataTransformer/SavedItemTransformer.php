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
     * @return array
     */
    protected static function addItemTags($savedItem, $relatedItemsTags)
    {
        $savedItem['tags'] = $relatedItemsTags[$savedItem['ui_id']];

        return $savedItem;
    }

    /**
     * @param array $savedItems
     * @param array $relatedItemsTags
     *
     * @return array
     */
    public static function addItemsTags($savedItems, $relatedItemsTags)
    {
        if (!count($savedItems)) {
            return [];
        }

        $result = [];
        foreach ($savedItems as $savedItem) {
            $result[] = self::addItemTags($savedItem, $relatedItemsTags);
        }

        return $result;
    }

    /**
     * @param array $savedItem
     *
     * @return array
     */
    protected static function transform($savedItem)
    {
        return [
            'article_id' => $savedItem['ui_id'],
            'feed_id' => $savedItem['feed_id'],
            'language' => $savedItem['language'] ?: $savedItem['item_language'],
            'link' => $savedItem['link'],
            'title' => $savedItem['title'],
            'content' => $savedItem['content'],
            'tags' => $savedItem['tags'],
        ];
    }

    /**
     * @param array $savedItems
     *
     * @return array
     */
    public static function transformList($savedItems)
    {
        if (!count($savedItems)) {
            return [];
        }

        $result = [];
        foreach ($savedItems as $savedItem) {
            $result[] = self::transform($savedItem);
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
                'article_id' => $uiId,
                'tags' => ArrayHelper::getIdsFromArray($itemData, 'tag_id')
            ];
        }

        return $result;
    }
}
