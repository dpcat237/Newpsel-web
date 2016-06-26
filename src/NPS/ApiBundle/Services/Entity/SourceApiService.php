<?php

namespace NPS\ApiBundle\Services\Entity;

use Doctrine\ORM\EntityManager;
use NPS\CoreBundle\Entity\SourceCategory;
use NPS\CoreBundle\Repository\SourceCategoryRepository;

/**
 * Class SourceApiService
 *
 * @package NPS\ApiBundle\Services\Entity
 */
class SourceApiService
{
    /** @var SourceCategoryRepository */
    protected $sourceCategoryRepository;

    /**
     * SourceApiService constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->sourceCategoryRepository  = $entityManager->getRepository(SourceCategory::class);
    }

    /**
     * @return SourceCategory[]
     */
    public function getPopularSources()
    {
        return $this->sourceCategoryRepository->findAll();
    }
}
