<?php
namespace NPS\CoreBundle\Services\Entity;

use Symfony\Component\Form\Form;
use NPS\CoreBundle\Entity\Feed,
    NPS\CoreBundle\Entity\User,
    NPS\CoreBundle\Entity\UserFeed;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Services\Entity\AbstractEntityService;

/**
 * FeedService
 */
class FeedService extends AbstractEntityService
{
    /**
     * Active subscription of subscribed user
     *
     * @param User $user
     * @param Feed $feed
     */
    protected function activateSubscribedUser(User $user, Feed $feed)
    {
        $userFeedRepo = $this->doctrine->getRepository('NPSCoreBundle:UserFeed');
        $whereUserFeed = array(
            'feed' => $feed->getId(),
            'user' => $user->getId()
        );
        $userFeed = $userFeedRepo->findOneBy($whereUserFeed);
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
            $this->systemNotification->setMessage(NotificationHelper::ALERT_FORM_DATA);
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
            $this->systemNotification->setMessage(NotificationHelper::ALERT_FORM_DATA);
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
        $feedRepo = $this->doctrine->getRepository('NPSCoreBundle:Feed');
        $feedSubscribed = $feedRepo->checkUserSubscribed($user->getId(), $feed->getId());
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
}
