<?php

namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\CoreBundle\Constant\RedisConstants;
use NPS\CoreBundle\Repository\LaterRepository;
use Predis\Client;
use Symfony\Component\Form\Form;
use NPS\CoreBundle\Entity\Later;
use NPS\CoreBundle\Entity\User;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * Class LaterService
 *
 * @package NPS\CoreBundle\Services\Entity
 */
class LaterService extends AbstractEntityService
{
    /** @var Client */
    protected $cache;

    /** @var LaterRepository */
    protected $tagRepository;

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
        $this->tagRepository = $this->entityManager->getRepository(Later::class);
    }

    /**
     * Get query of user labels
     *
     * @return string
     */
    public function getUserLabelsQuery()
    {
        return $this->tagRepository->getUserLabelsQuery($this->userWrapper->getCurrentUser()->getId());
    }

    /**
     * Remove label
     *
     * @param Later $label
     */
    public function removeLabel(Later $label)
    {
        $this->removeObject($label);
    }

    /**
     * Save form of user label to data base
     *
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
     * @param      $name
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
        $value = $this->cache->get(RedisConstants::LABEL_MENU_ALL . "_" . $userId);
        $value = ($value == 1) ? 0 : 1;
        $this->cache->set(RedisConstants::LABEL_MENU_ALL . "_" . $userId, $value);
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
        $value = $this->cache->get(RedisConstants::LABEL_MENU_ALL . "_" . $userId);

        return ($value == 1) ? true : false;
    }
}
