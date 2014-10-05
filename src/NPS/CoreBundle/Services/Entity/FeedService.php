<?php
namespace NPS\CoreBundle\Services\Entity;

use NPS\CoreBundle\Constant\RedisConstants;
use NPS\CoreBundle\Services\NotificationManager;
use NPS\CoreBundle\Services\UserWrapper;
use Symfony\Component\Form\Form;
use NPS\CoreBundle\Entity\Feed,
    NPS\CoreBundle\Entity\User,
    NPS\CoreBundle\Entity\UserFeed;
use NPS\CoreBundle\Helper\NotificationHelper;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Predis\Client;

/**
 * FeedService
 */
class FeedService extends AbstractEntityService
{
    /**
     * @var Client
     */
    private $cache;


    /**
     * @param Registry            $doctrine     Registry
     * @param Client              $cache     Client
     * @param UserWrapper         $userWrapper  UserWrapper
     * @param NotificationManager $notification NotificationManager
     */
    public function __construct(Registry $doctrine, Client $cache, UserWrapper $userWrapper, NotificationManager $notification)
    {
        parent::__construct($doctrine, $userWrapper, $notification);
        $this->cache = $cache;
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
        $feedRepo = $this->doctrine->getRepository('NPSCoreBundle:Feed');
        $feed = $feedRepo->checkExistFeedUrl($url);

        return $feed;
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
        $userFeedRepo = $this->doctrine->getRepository('NPSCoreBundle:UserFeed');
        $whereUserFeed = array(
            'feed' => $feedId,
            'user' => $userId
        );
        $userFeed = $userFeedRepo->findOneBy($whereUserFeed);

        return $userFeed;
    }

    /**
     * Get query of user active feeds
     *
     * @return string
     */
    public function getUserActiveFeedsQuery()
    {
        $feedRepo = $this->doctrine->getRepository('NPSCoreBundle:Feed');
        $query = $feedRepo->getUserFeedsQuery($this->userWrapper->getCurrentUser()->getId());

        return $query;
    }

    /**
     * Soft remove user's feed
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
        $userFeedRepo = $this->doctrine->getRepository('NPSCoreBundle:UserFeed');
        $feedSubscribed = $userFeedRepo->checkUserSubscribed($user->getId(), $feed->getId());
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
        $userFeedRepo = $this->entityManager->getRepository('NPSCoreBundle:UserFeed');
        if ($userFeedRepo->countActiveSubscribers($feed->getId()) < 1) {
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
        $value = $this->cache->get(RedisConstants::FEED_MENU_ALL."_".$userId);
        $value = ($value == 1)? 0 : 1;
        $this->cache->set(RedisConstants::FEED_MENU_ALL."_".$userId, $value);
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
        $value = $this->cache->get(RedisConstants::FEED_MENU_ALL."_".$userId);

        return ($value == 1)? true : false;
    }
}
