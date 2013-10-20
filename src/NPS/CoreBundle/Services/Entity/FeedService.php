<?php
namespace NPS\CoreBundle\Services\Entity;

use Symfony\Component\Form\Form;
use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Services\Entity\AbstractEntityService;

/**
 * FeedService
 */
class FeedService extends AbstractEntityService
{
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
}
