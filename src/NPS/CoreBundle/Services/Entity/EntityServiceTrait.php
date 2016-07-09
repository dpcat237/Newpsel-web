<?php

namespace NPS\CoreBundle\Services\Entity;

use Doctrine\ORM\EntityManager;

/**
 * Class FeedService
 *
 * @package NPS\CoreBundle\Services\Entity
 */
trait EntityServiceTrait
{
    /** @var EntityManager */
    protected $entityManager;

    /**
     * Save object to data base
     *
     * @param      $object
     * @param bool $flushAlone
     */
    public function saveObject($object, $flushAlone = true)
    {
        $this->entityManager->persist($object);
        if ($flushAlone) {
            $this->entityManager->flush($object);
        } else {
            $this->entityManager->flush();
        }
    }
}
