<?php
namespace NPS\ApiBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Helper\ArrayHelper;
use NPS\CoreBundle\Services\CrawlerService,
    NPS\CoreBundle\Services\Entity\LaterService,
    NPS\CoreBundle\Services\Entity\LaterItemService;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * LabelApiService
 */
class LabelApiService
{
    /**
     * @var CrawlerService
     */
    private $crawler;

    /**
     * @var Doctrine Registry
     */
    private $doctrine;

    /**
     * @var LaterService
     */
    private $labelService;

    /**
     * @var LaterItemService
     */
    private $labelItemService;

    /**
     * @var SecureService
     */
    private $secure;


    /**
     * @param CrawlerService   $crawler          CrawlerService
     * @param Registry         $doctrine         Doctrine Registry
     * @param SecureService    $secure           SecureService
     * @param LaterService     $labelService     LaterService
     * @param LaterItemService $labelItemService LaterItemService
     */
    public function __construct(CrawlerService $crawler, Registry $doctrine, SecureService $secure, LaterService $labelService, LaterItemService $labelItemService)
    {
        $this->crawler = $crawler;
        $this->doctrine = $doctrine;
        $this->labelService = $labelService;
        $this->labelItemService = $labelItemService;
        $this->secure = $secure;
    }

    /**
     * Get user labels from app key for Chrome api
     * @param $appKey
     *
     * @return array
     */
    public function getUserLabels($appKey)
    {
        $response = false;
        $labels = array();

        $user = $this->secure->getUserByDevice($appKey);
        if ($user instanceof User) {
            $labelRepo = $this->doctrine->getRepository('NPSCoreBundle:Later');
            $orderBy = array('name' => 'ASC');
            $labelsData = $labelRepo->findByUser($user, $orderBy);

            //prepare labels for api
            foreach ($labelsData as $lab) {
                $label['id'] = $lab->getId();
                $label['name'] = $lab->getName();
                $labels[] = $label;
            }
            $response = true;
        }
        $responseData = array(
            'response' => $response,
            'labels' => $labels
        );

        return $responseData;
    }

    /**
     * Sync labels
     * @param $appKey
     * @param $changedLabels
     * @param $lastUpdate
     *
     * @return array
     */
    public function syncLabels($appKey, $changedLabels, $lastUpdate)
    {
        $error = false;
        $labelCollection = array();

        $user = $this->secure->getUserByDevice($appKey);
        if (!$user instanceof User) {
            $error = NotificationHelper::ERROR_NO_LOGGED;
        }

        if (empty($error)){
            $labelCollection = $this->labelService->syncLabelsApi($user, $changedLabels, $lastUpdate);
        }
        $responseData = array(
            'error' => $error,
            'labelCollection' => $labelCollection,
        );

        return $responseData;
    }

    /**
     * Sync later item from API to database to be read later
     *
     * @param array $appKey     login key
     * @param array $laterItems selected items to be read later
     *
     * @return array
     */
    public function syncLaterItemsApi($appKey, $laterItems)
    {
        $error = false;
        $result = false;

        $user = $this->secure->getUserByDevice($appKey);
        if (!$user instanceof User) {
            $error = NotificationHelper::ERROR_NO_LOGGED;
            $result = NotificationHelper::ERROR_NO_LOGGED;
        }

        if (empty($error) && is_array($laterItems) && count($laterItems)){
            $this->labelService->syncLaterItems($user->getId(), $laterItems);
            //get complete content for partial articles
            $this->crawler->executeCrawling($user->getId());

            $result = NotificationHelper::OK;
        }
        $responseData = array(
            'error' => $error,
            'result' => $result,
        );

        return $responseData;
    }

    /**
     * Sync later item to API and update the reviewed items
     *
     * @param array $appKey       login key
     * @param array $dictateItems items for dictation
     * @param int   $labelId      label id
     * @param int   $limit        limit of dictations to sync
     *
     * @return array
     */
    public function syncDictateItems($appKey, $dictateItems, $labelId, $limit)
    {
        $error = false;
        $result = array();

        $user = $this->secure->getUserByDevice($appKey);
        if (!$user instanceof User) {
            $error = NotificationHelper::ERROR_NO_LOGGED;
            $result = NotificationHelper::ERROR_NO_LOGGED;
        }

        list($readItems, $unreadItems) = ArrayHelper::separateUnreadArray($dictateItems);
        if (empty($error) && is_array($readItems) && count($readItems)) {
            $this->doctrine->getRepository('NPSCoreBundle:LaterItem')->syncViewedLaterItems($readItems);
        }
        if (empty($error)) {
            $result = $this->labelItemService->getUnreadItemsApi($labelId, $unreadItems, $limit);
        }
        $responseData = array(
            'error' => $error,
            'result' => $result,
        );

        return $responseData;
    }

}
