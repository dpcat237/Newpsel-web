<?php
namespace NPS\CoreBundle\Services\Entity;

use Symfony\Component\Form\Form;
use NPS\CoreBundle\Entity\Feed,
    NPS\CoreBundle\Entity\UserFeed;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Services\Entity\AbstractEntityService;

/**
 * FeedService
 */
class FeedService extends AbstractEntityService
{
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

    protected function updateFeedStatus(Feed $feed)
    {
        $userFeedRepo = $this->entityManager->getRepository('NPSCoreBundle:UserFeed');
        if ($userFeedRepo->countActiveSubscribers($feed->getId()) < 1) {
            $feed->setEnabled(false);
            $this->saveObject($feed);
        }
    }
}
