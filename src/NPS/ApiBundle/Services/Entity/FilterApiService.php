<?php

namespace NPS\ApiBundle\Services\Entity;

use NPS\ApiBundle\Exception\NoDataException;
use NPS\ApiBundle\Exception\PremiumPermissionsException;
use NPS\CoreBundle\Entity\Filter;
use NPS\CoreBundle\Helper\PremiumUserHelper;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Services\Entity\FeedService;
use NPS\CoreBundle\Services\Entity\FilterService;

/**
 * Class FeedApiService
 *
 * @package NPS\ApiBundle\Services\Entity
 */
class FilterApiService
{
    /** @var FeedService */
    protected $feedService;

    /** @var FilterService */
    protected $filterService;

    /**
     * FeedApiService constructor.
     *
     * @param FilterService $filterService
     * @param FeedService   $feedService
     */
    public function __construct(FilterService $filterService, FeedService $feedService)
    {
        $this->feedService   = $feedService;
        $this->filterService = $filterService;
    }

    /**
     * Add feeds to automatically add their articles to dictation
     *
     * @param User  $user
     * @param array $feedIds
     */
    public function automaticallyToDictation(User $user, $feedIds)
    {
        if (!PremiumUserHelper::autoFeedToDictationPermissions($user)) {
            throw new PremiumPermissionsException();
        }

        if (!count($feedIds)) {
            throw new NoDataException();
        }

        $this->filterService->createFilterFeeds(
            $user,
            Filter::FILTER_TO_TAG,
            'Feeds to dictation',
            $user->getPreference()->getDictationTag(),
            $this->feedService->getFeedsByIds($feedIds)
        );
    }
}
