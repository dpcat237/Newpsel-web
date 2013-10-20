<?php
namespace NPS\CoreBundle\Services\Entity;

use Symfony\Component\Form\Form;
use NPS\CoreBundle\Entity\Later;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Services\Entity\AbstractEntityService;

/**
 * LabelService
 */
class LabelService extends AbstractEntityService
{
    /**
     * Remove label
     * @param Later $label
     */
    public function removeLabel(Later $label)
    {
        $this->removeObject($label);
    }

    /**
     * Save form of feed to data base
     * @param Form $form
     */
    public function saveFormLabel(Form $form)
    {
        $formObject = $form->getData();
        if ($form->isValid() && $formObject instanceof Later) {
            $this->saveObject($formObject, true);
        } else {
            $this->systemNotification->setMessage(NotificationHelper::ALERT_FORM_DATA);
        }
    }
}
