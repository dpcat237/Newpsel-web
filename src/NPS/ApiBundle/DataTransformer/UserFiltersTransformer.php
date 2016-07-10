<?php

namespace NPS\ApiBundle\DataTransformer;

use NPS\CoreBundle\Entity\Filter;
use NPS\CoreBundle\Entity\FilterFeed;

/**
 * Class UserFiltersTransformer
 *
 * @package NPS\ApiBundle\DataTransformer
 */
class UserFiltersTransformer
{
    /**
     * Prepares array output for enabled status request
     *
     * @param Filter $filter
     *
     * @return array
     */
    public static function transform(Filter $filter)
    {
        return [
            'id'     => $filter->getId(),
            'type'   => $filter->getType(),
            'tag_id' => $filter->getLater()->getId(),
            'feeds'  => self::getFeedsIds($filter->getFilterFeeds())
        ];
    }

    /**
     * Prepares array output for list status request
     *
     * @param Filter[] $filters
     *
     * @return array
     */
    public static function transformList(array $filters)
    {
        return array_map(
            function (Filter $filter) {
                return self::transform($filter);
            },
            $filters
        );
    }

    /**
     * Gets feeds ids
     *
     * @param FilterFeed[] $filterFeeds
     *
     * @return array
     */
    protected static function getFeedsIds($filterFeeds)
    {
        $feedsIds = [];
        foreach ($filterFeeds as $filterFeed) {
            $feedsIds [] = $filterFeed->getFeed()->getId();
        }

        return $feedsIds;
    }
}
