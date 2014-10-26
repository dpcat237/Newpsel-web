<?php
namespace NPS\CoreBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Dpcat237\CrawlerBundle\Library\Crawler;
use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Entity\Item;
use NPS\CoreBundle\Event\FeedCreatedEvent;
use NPS\CoreBundle\Repository\ItemRepository;
use NPS\CoreBundle\Services\LanguageDetectService;

class FeedListener
{
    /**
     * @var Crawler
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
     * @param Crawler               $crawler          Crawler
     */
    public function __construct(ItemRepository $itemRepository, LanguageDetectService $languageDetector, EntityManager $entityManager, Crawler $crawler)
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
        $items = $this->itemRepository->getByFeed($feed->getId(), 10);
        foreach ($items as $item) {
            if ($this->detectItemLanguage($feed, $item)) {
                break;
            }
        }
    }

    /**
     * Detect item content language and set to his feed
     *
     * @param Feed $feed
     * @param Item $item
     *
     * @return bool
     */
    private function detectItemLanguage(Feed $feed, Item $item)
    {
        $content = strip_tags($item->getContent());
        $limit = (strlen($content) < 250)? strlen($content) : 250;
        if ($limit < 100) {
            return false;
        }

        $content = substr($content, 0, $limit);
        $languageCode = $this->languageDetector->detectLanguage($content);

        $feed->setLanguage($languageCode);
        $this->persist = true;

        return true;
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

