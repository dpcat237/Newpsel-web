<?php
namespace NPS\CoreBundle\EventListener;

use Doctrine\ORM\EntityManager;
use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Entity\Item;
use NPS\CoreBundle\Event\FeedCreatedEvent;
use NPS\CoreBundle\Repository\ItemRepository;
use NPS\CoreBundle\Services\LanguageDetectService;
use NPS\CoreBundle\Services\CrawlerManager;

class FeedListener
{
    /**
     * @var CrawlerManager
     */
    private $crawler;

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
     * @var boolean
     */
    private $persist = false;

    /**
     * @param ItemRepository        $itemRepository   Item Repository
     * @param LanguageDetectService $languageDetector LanguageDetectService
     * @param EntityManager         $entityManager    Entity Manager
     * @param CrawlerManager        $crawler          CrawlerManager
     */
    public function __construct(ItemRepository $itemRepository, LanguageDetectService $languageDetector, EntityManager $entityManager, CrawlerManager $crawler)
    {
        $this->crawler = $crawler;
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
        $this->persist = false;
        $feed = $event->getFeed();
        $this->setFeedLanguage($feed);
        //$this->detectNecessaryCrawling($feed);
        if (!$this->persist) {
            return;
        }

        $this->entityManager->persist($feed);
        $this->entityManager->flush();
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
        $this->persist = true;
    }

    /**
     * Detect if it's necessary crawling and set it
     *
     * @param Feed $feed
     */
    private function detectNecessaryCrawling(Feed $feed)
    {
        $items = $this->itemRepository->findByFeed($feed);
        $crawling = false;
        foreach ($items as $item) {
            $fullContent = $this->crawler->getFullArticle($item->getLink());
            if ((strlen($item->getContent())+1000) < strlen($fullContent)) {
                $crawling = true;
                break;
            }
        }

        if (!$crawling) {
            $feed->setCrawling(true);
            $this->persist = true;
        }
    }
}

