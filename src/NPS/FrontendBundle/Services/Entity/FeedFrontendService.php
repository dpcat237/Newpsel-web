<?php

namespace NPS\FrontendBundle\Services\Entity;

use NPS\CoreBundle\Constant\RedisConstants;
use NPS\CoreBundle\Services\Entity\FeedService;
use Symfony\Component\Form\Form;
use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Entity\UserFeed;
use NPS\CoreBundle\Helper\NotificationHelper;
use Predis\Client;

/**
 * Class FeedFrontendService
 *
 * @package NPS\FrontendBundle\Services\Entity
 */
class FeedFrontendService extends AbstractEntityFrontendService
{
    /** @var Client */
    protected $cache;

    /** @var FeedService */
    protected $feedService;

    /**
     * @param FeedService $feedService
     */
    public function setFeedService(FeedService $feedService)
    {
        $this->feedService = $feedService;
    }

    /**
     * @param Client $cache
     */
    public function setRedis(Client $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @inheritdoc
     */
    public function checkFeedByUrl($url)
    {
        return $this->feedService->checkFeedByUrl($url);
    }

    /**
     * @inheritdoc
     */
    public function getUserFeed($userId, $feedId)
    {
        return $this->feedService->getUserFeed($userId, $feedId);
    }

    /**
     * @inheritdoc
     */
    public function getUserActiveFeedsQuery()
    {
        return $this->feedService->getUserActiveFeedsQuery($this->userWrapper->getCurrentUser());
    }

    /**
     * @inheritdoc
     */
    public function removeUserFeed(UserFeed $userFeed)
    {
        $this->feedService->removeUserFeed($userFeed);
    }

    /**
     * Save form of feed to data base
     *
     * @param Form $form
     */
    public function saveFormFeed(Form $form)
    {
        $formObject = $form->getData();
        if ($form->isValid() && $formObject instanceof Feed) {
            $this->saveNotification($this->feedService, $formObject);
        } else {
            $this->notification->setFlashMessage(NotificationHelper::ALERT_FORM_DATA);
        }
    }

    /**
     * Save form of feed to data base
     *
     * @param Form $form
     */
    public function saveFormUserFeed(Form $form)
    {
        $formObject = $form->getData();
        if ($form->isValid() && $formObject instanceof UserFeed) {
            $this->saveNotification($this->feedService, $formObject);
        } else {
            $this->notification->setFlashMessage(NotificationHelper::ALERT_FORM_DATA);
        }
    }

    /**
     * @inheritdoc
     */
    public function subscribeUser(User $user, Feed $feed)
    {
        $this->feedService->subscribeUser($user, $feed);
    }

    /**
     * Change status if show all feeds in menu or only with new items
     *
     * @param int $userId
     */
    public function changeMenuAll($userId)
    {
        $value = $this->cache->get(RedisConstants::FEED_MENU_ALL . "_" . $userId);
        $value = ($value == 1) ? 0 : 1;
        $this->cache->set(RedisConstants::FEED_MENU_ALL . "_" . $userId, $value);
    }

    /**
     * Get status if show all feeds in menu or only with new items
     *
     * @param int $userId
     *
     * @return bool
     */
    public function getMenuAll($userId)
    {
        $value = $this->cache->get(RedisConstants::FEED_MENU_ALL . "_" . $userId);

        return ($value == 1) ? true : false;
    }
}
