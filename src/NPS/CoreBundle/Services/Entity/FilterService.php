<?php

namespace NPS\CoreBundle\Services\Entity;

use Doctrine\ORM\EntityManager;
use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Entity\Filter;
use NPS\CoreBundle\Entity\Later;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Repository\FilterRepository;

/**
 * Class FilterService
 *
 * @package NPS\CoreBundle\Services\Entity
 */
class FilterService
{
    use EntityServiceTrait;

    /** @var FilterRepository */
    protected $filterRepository;

    /**
     * FeedService constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager    = $entityManager;
        $this->filterRepository = $entityManager->getRepository(Filter::class);
    }

    /**
     * Create filter in principal data base
     *
     * @param User   $user
     * @param string $type
     * @param string $name
     * @param Later  $tag
     * @param Feed[] $feeds
     */
    public function createFilterFeeds(User $user, $type, $name, $tag, $feeds)
    {
        $filter = new Filter();
        $filter
            ->setType($type)
            ->setUser($user)
            ->setName($name)
            ->setLater($tag);
        foreach ($feeds as $feed) {
            $filter->addFeed($feed);
        }

        $this->entityManager->persist($filter);
        $this->entityManager->flush();
    }

    /**
     * Remove user's filter
     *
     * @param Filter $filter
     */
    public function removeFilter(Filter $filter)
    {
        foreach ($filter->getFilterFeeds() as $filterFeed) {
            $this->removeObject($filterFeed);
        }

        $this->removeObject($filter);
    }
}
