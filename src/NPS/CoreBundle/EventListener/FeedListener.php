<?php
namespace NPS\CoreBundle\EventListener;

use Doctrine\ORM\EntityManager;
use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Entity\Item;
use NPS\CoreBundle\Event\FeedCreatedEvent;
use NPS\CoreBundle\Repository\ItemRepository;
use NPS\CoreBundle\Services\LanguageDetectService;

class FeedListener
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ItemRepository
     */
    private $itemRepository;

    /**
     * @var LanguageDetectService
     */
    private $languageDetector;

    /**
     * @param ItemRepository        $itemRepository   Item Repository
     * @param LanguageDetectService $languageDetector LanguageDetectService
     * @param EntityManager         $entityManager    Entity Manager
     */
    public function __construct(ItemRepository $itemRepository, LanguageDetectService $languageDetector, EntityManager $entityManager)
    {
        $this->itemRepository = $itemRepository;
        $this->languageDetector = $languageDetector;
        $this->entityManager = $entityManager;
    }

    /**
     * Make necessary processes after was created new feed
     *
     * @param FeedCreatedEvent $event
     */
    public function onFeedCreated(FeedCreatedEvent $event)
    {
        $feed = $event->getFeed();
        $this->setFeedLanguage($feed);
    }

    /**
     * Detect feed language from random item
     *
     * @param Feed $feed
     */
    private function setFeedLanguage(Feed $feed)
    {
        $item = $this->itemRepository->findOneByFeed($feed);
        if (!$item instanceof Item) {
            return;
        }

        $content = strip_tags($item->getContent());
        $content = substr($content, 0, 70);
        if (strlen($content) < 20) {
            return;
        }
        $languageCode = $this->languageDetector->detectLanguage($content);
        $feed->setLanguage($languageCode);
        $this->entityManager->persist($feed);
        $this->entityManager->flush();
    }
}

