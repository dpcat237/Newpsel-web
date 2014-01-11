<?php
namespace NPS\ApiBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\ApiBundle\Services\SecureService;
use NPS\CoreBundle\Services\CrawlerService,
    NPS\CoreBundle\Services\Entity\LabelService;
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
     * @var LabelService
     */
    private $labelService;

    /**
     * @var SecureService
     */
    private $secure;


    /**
     * @param CrawlerService $crawler      CrawlerService
     * @param Registry       $doctrine     Doctrine Registry
     * @param SecureService  $secure       SecureService
     * @param LabelService   $labelService LabelService
     */
    public function __construct(CrawlerService $crawler, Registry $doctrine, SecureService $secure, LabelService $labelService)
    {
        $this->crawler = $crawler;
        $this->doctrine = $doctrine;
        $this->labelService = $labelService;
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
     * Sync later item from API to database
     *
     * @param $appKey
     * @param $laterItems
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
}
