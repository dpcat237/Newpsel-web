<?php

namespace NPS\CoreBundle\Services\Entity;

use NPS\CoreBundle\Constant\RedisConstants;
use NPS\CoreBundle\Repository\FeedRepository;
use NPS\CoreBundle\Repository\UserFeedRepository;
use Symfony\Component\Form\Form;
use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Entity\UserFeed;
use NPS\CoreBundle\Helper\NotificationHelper;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Predis\Client;

/**
 * Class FeedService
 *
 * @package NPS\CoreBundle\Services\Entity
 */
class FeedService extends AbstractEntityService
{
    /** @var Client */
    protected $cache;

    /** @var FeedRepository */
    protected $feedRepository;

    /** @var UserFeedRepository */
    protected $userFeedRepository;

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
    protected function setRepository()
    {
        $this->feedRepository     = $this->entityManager->getRepository(Feed::class);
        $this->userFeedRepository = $this->entityManager->getRepository(UserFeed::class);
    }

    /**
     * Active subscription of subscribed user
     *
     * @param User $user
     * @param Feed $feed
     */
    protected function activateSubscribedUser(User $user, Feed $feed)
    {
        $userFeed = $this->getUserFeed($user->getId(), $feed->getId());
        $userFeed->setDeleted(false);
        $this->entityManager->persist($userFeed);
        $this->entityManager->flush();
    }

    /**
     * Check if exist feed by url and return it
     *
     * @param $url
     *
     * @return Feed
     */
    public function checkFeedByUrl($url)
    {
        return $this->feedRepository->checkExistFeedUrl($url);
    }

    /**
     * Get UserFeed object
     *
     * @param int $userId
     * @param int $feedId
     *
     * @return UserFeed
     */
    public function getUserFeed($userId, $feedId)
    {
        $whereUserFeed = [
            'feed' => $feedId,
            'user' => $userId
        ];
        $userFeed      = $this->userFeedRepository->findOneBy($whereUserFeed);

        return $userFeed;
    }

    /**
     * Get query of user active feeds
     *
     * @return string
     */
    public function getUserActiveFeedsQuery()
    {
        return $this->feedRepository->getUserFeedsQuery($this->userWrapper->getCurrentUser()->getId());
    }

    /**
     * Soft remove user's feed
     *
     * @param UserFeed $userFeed
     */
    public function removeUserFeed(UserFeed $userFeed)
    {
        $userFeed->setDeleted(true);
        $feed = $userFeed->getFeed();
        $this->saveObject($userFeed);
        $this->updateFeedStatus($feed);
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
            $this->saveObject($formObject, true);
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
            $this->saveObject($formObject, true);
        } else {
            $this->notification->setFlashMessage(NotificationHelper::ALERT_FORM_DATA);
        }
    }

    /**
     * Subscribe user to feed
     *
     * @param User $user User
     * @param Feed $feed Feed
     */
    public function subscribeUser(User $user, Feed $feed)
    {
        $feedSubscribed = $this->userFeedRepository->checkUserSubscribed($user->getId(), $feed->getId());
        if ($feedSubscribed) {
            $this->activateSubscribedUser($user, $feed);
        } else {
            $this->subscribeNewUser($user, $feed);
        }
    }

    /**
     * Subscribe new user
     *
     * @param User $user
     * @param Feed $feed
     */
    public function subscribeNewUser(User $user, Feed $feed)
    {
        $userFeed = new UserFeed();
        $userFeed->setUser($user);
        $userFeed->setFeed($feed);
        $userFeed->setTitle($feed->getTitle());
        $feed->setEnabled(true);
        $this->entityManager->persist($userFeed);
        $this->entityManager->flush();

        $feed->addUserFeed($userFeed);
    }

    /**
     * Update feed enabled status to false if are subscribers
     *
     * @param Feed $feed
     */
    protected function updateFeedStatus(Feed $feed)
    {
        if ($this->userFeedRepository->countActiveSubscribers($feed->getId()) < 1) {
            $feed->setEnabled(false);
            $this->saveObject($feed);
        }
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
