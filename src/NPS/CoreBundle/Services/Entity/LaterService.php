<?php
namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\CoreBundle\Constant\RedisConstants;
use Predis\Client;
use NPS\CoreBundle\Services\NotificationManager;
use NPS\CoreBundle\Services\UserWrapper;
use Symfony\Component\Form\Form;
use NPS\CoreBundle\Entity\Later;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * LaterService
 */
class LaterService extends AbstractEntityService
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
     * Get query of user labels
     *
     * @return string
     */
    public function getUserLabelsQuery()
    {
        $labelRepo = $this->doctrine->getRepository('NPSCoreBundle:Later');
        $query = $labelRepo->getUserLabelsQuery($this->userWrapper->getCurrentUser()->getId());

        return $query;
    }

    /**
     * Remove label
     *
     * @param Later $label
     */
    public function removeLabel(Later $label)
    {
        //set to cache that label was deleted to notify API
        $deletedLabels = $this->cache->get(RedisConstants::LABEL_DELETED."_".$label->getUserId());
        if (empty($deletedLabels)) {
            $deletedLabels[] = $label->getId();
        } else {
            $deletedLabels = explode(',', $deletedLabels);
            if (!in_array($label->getId(), $deletedLabels)) {
                $deletedLabels[] = $label->getId();
            }
        }
        $deletedLabels = implode(',', $deletedLabels);
        $this->cache->set(RedisConstants::LABEL_DELETED."_".$label->getUserId(), $deletedLabels);

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
            $this->notification->setFlashMessage(NotificationHelper::ALERT_FORM_DATA);
        }
    }

    /**
     * Create new Label
     *
     * @param User $user
     * @param $name
     * @param null $dateUp
     *
     * @return Later
     */
    public function createLabel(User $user, $name, $dateUp = null)
    {
        $label = new Later();
        $label->setUser($user);
        $label->setName($name);
        if ($dateUp) {
            $label->setDateUp($dateUp);
        }
        $this->entityManager->persist($label);
        $this->entityManager->flush();

        return $label;
    }

    /**
     * Change status if show all labels in menu or only with new items
     *
     * @param int $userId
     */
    public function changeMenuAll($userId)
    {
        $value = $this->cache->get(RedisConstants::LABEL_MENU_ALL."_".$userId);
        $value = ($value == 1)? 0 : 1;
        $this->cache->set(RedisConstants::LABEL_MENU_ALL."_".$userId, $value);
    }

    /**
     * Get status if show all labels in menu or only with new items
     *
     * @param int $userId
     *
     * @return bool
     */
    public function getMenuAll($userId)
    {
        $value = $this->cache->get(RedisConstants::LABEL_MENU_ALL."_".$userId);

        return ($value == 1)? true : false;
    }
}
