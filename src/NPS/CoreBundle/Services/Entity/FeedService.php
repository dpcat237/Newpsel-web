<?php

namespace NPS\CoreBundle\Services\Entity;

use Doctrine\ORM\EntityManager;
use NPS\CoreBundle\Repository\FeedRepository;
use NPS\CoreBundle\Repository\UserFeedRepository;
use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Entity\UserFeed;

/**
 * Class FeedService
 *
 * @package NPS\CoreBundle\Services\Entity
 */
class FeedService
{
    use EntityServiceTrait;

    /** @var FeedRepository */
    protected $feedRepository;

    /** @var UserFeedRepository */
    protected $userFeedRepository;

    /**
     * FeedService constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager      = $entityManager;
        $this->feedRepository     = $entityManager->getRepository(Feed::class);
        $this->userFeedRepository = $entityManager->getRepository(UserFeed::class);
    }

    /**
     * Active subscription of subscribed user
     *
     * @param User $user
     * @param Feed $feed
     */
    public function activateSubscribedUser(User $user, Feed $feed)
    {
        $userFeed = $this->getUserFeed($user->getId(), $feed->getId());
        $userFeed->setDeleted(false);
        $this->entityManager->persist($userFeed);
        $this->entityManager->flush($userFeed);
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
     * Change user feed title
     *
     * @param int    $userId
     * @param int    $feedId
     * @param string $feedTitle
     */
    public function editUserFeed($userId, $feedId, $feedTitle)
    {
        $userFeed = $this->getUserFeed($userId, $feedId);
        $userFeed->setTitle($feedTitle);
        $this->entityManager->flush($userFeed);
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

        return $this->userFeedRepository->findOneBy($whereUserFeed);
    }

    /**
     * @inheritdoc
     */
    public function getUserFeedsApi($userId)
    {
        return $this->feedRepository->getUserFeedsApi($userId);
    }

    /**
     * Get query of user active feeds
     *
     * @param User $user
     *
     * @return string
     */
    public function getUserActiveFeedsQuery(User $user)
    {
        return $this->feedRepository->getUserFeedsQuery($user->getId());
    }

    /**
     * Soft remove user's feed
     *
     * @param UserFeed $userFeed
     */
    public function removeUserFeed(UserFeed $userFeed)
    {
        $userFeed->setDeleted(true);
        $this->updateFeedStatus($userFeed->getFeed());
        $this->entityManager->flush();
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
        }
    }

    /**
     * @inheritdoc
     */
    public function updateUserFeed($feedId, $title, $dateUp)
    {
        $this->feedRepository->updateFeed($feedId, $title, $dateUp);
    }
}
