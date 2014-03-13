<?php
namespace NPS\CoreBundle\Services\Entity;

use Symfony\Component\Form\Form;
use NPS\CoreBundle\Entity\Later,
    NPS\CoreBundle\Entity\LaterItem,
    NPS\CoreBundle\Entity\User,
    NPS\CoreBundle\Entity\UserItem;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Services\Entity\AbstractEntityService;

/**
 * LabelService
 */
class LabelService extends AbstractEntityService
{
    /**
     * Extract user labels data for api
     *
     * @param $collection
     * @param $createdIds
     *
     * @return array
     */
    private function addLabelsApiIds($collection, $createdIds){
        foreach ($collection as $key => $value) {
            $collection[$key]['id'] = 0;
            if (count($createdIds)) {
                foreach ($createdIds as $apiId => $webId) {
                    if ($value['api_id'] == $webId) {
                        $collection[$key]['id'] = $apiId;
                    }
                }
            }
        }

        return $collection;
    }

    /**
     * Remove label
     * @param Later $label
     */
    public function removeLabel(Later $label)
    {
        $this->removeObject($label);
    }

    /**
     * Save form of user label to data base
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

    public function syncLabelsApi(User $user, $changedLabels, $lastUpdate)
    {
        $labelRepo = $this->doctrine->getRepository('NPSCoreBundle:Later');
        if (count($changedLabels)) {
            $createdIds = $this->syncLabelsProcess($user, $changedLabels);
            $createdCollection = $labelRepo->getUserLabelsApiCreated($user->getId(), $lastUpdate, $changedLabels, $createdIds);
            $labelCollection = $this->addLabelsApiIds($createdCollection, $createdIds);
        } else {
            $labelCollection = $labelRepo->getUserLabelsApi($user->getId(), $lastUpdate);
        }

        return $labelCollection;
    }

    /**
     * Sync labels from device
     *
     * @param User $user
     * @param $changedLabels
     *
     * @return array
     */
    public function syncLabelsProcess(User $user, $changedLabels)
    {
        $createdIds = null;
        $laterRepo = $this->doctrine->getRepository('NPSCoreBundle:Later');
        foreach ($changedLabels as $changedLabel) {
            if ($changedLabel['id']) {
                $label = $laterRepo->find($changedLabel['id']);
                $label->setName($changedLabel['name']);
                $this->entityManager->persist($label);
                $this->entityManager->flush();
            } else {
                $label = $this->hasLabelByName($user->getId(), $changedLabel['name']);
                if (!$label instanceof Later) {
                    $label = new Later();
                    $label->setUser($user);
                }
                $label->setName($changedLabel['name']);
                $this->entityManager->persist($label);
                $this->entityManager->flush();

                $createdIds[$changedLabel['api_id']] = $label->getId();
            }
        }

        return $createdIds;
    }

    /**
     * Add later items for specific user
     *
     * @param integer $userId
     * @param array   $items
     */
    public function syncLaterItems($userId, $items)
    {
        $userItemRepo = $this->doctrine->getRepository('NPSCoreBundle:UserItem');
        $laterItemRepo = $this->doctrine->getRepository('NPSCoreBundle:LaterItem');
        $laterRepo = $this->doctrine->getRepository('NPSCoreBundle:Later');

        foreach ($items as $itemData) {
            $itemId = $itemData['item_id'];
            $labelId = $itemData['label_id'];
            $userItem = $userItemRepo->hasItem($userId, $itemId);
            if ($userItem instanceof UserItem) {
                $laterItem = $laterItemRepo->laterExists($labelId, $userItem->getId());
                if ($laterItem instanceof LaterItem) {
                    $laterItem->setUnread(true);
                    $this->entityManager->persist($laterItem);
                } else {
                    $laterItem = new LaterItem();
                    $laterItem->setLater($laterRepo->find($labelId));
                    $laterItem->setUserItem($userItem);
                    $this->entityManager->persist($laterItem);
                }
            }
        }
        $this->entityManager->flush();
    }
}
