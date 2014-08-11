<?php
namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use NPS\CoreBundle\Constant\RedisConstants;
use Predis\Client;
use NPS\CoreBundle\Services\NotificationManager;
use NPS\CoreBundle\Services\UserWrapper;
use Symfony\Component\Form\Form;
use NPS\CoreBundle\Entity\Later,
    NPS\CoreBundle\Entity\LaterItem,
    NPS\CoreBundle\Entity\User,
    NPS\CoreBundle\Entity\UserItem;
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
     * @var LaterItemService
     */
    protected $laterItem;


    /**
     * @param Registry            $doctrine     Registry
     * @param Client           $cache     Client
     * @param UserWrapper         $userWrapper  UserWrapper
     * @param NotificationManager $notification NotificationManager
     * @param LaterItemService    $laterItem LaterItemService
     */
    public function __construct(Registry $doctrine, Client $cache, UserWrapper $userWrapper, NotificationManager $notification, LaterItemService $laterItem)
    {
        parent::__construct($doctrine, $userWrapper, $notification);
        $this->cache = $cache;
        $this->laterItem = $laterItem;
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
     * Add later items for specific user
     *
     * @param integer $userId
     * @param array   $items
     */
    public function syncLaterItems($userId, $items)
    {
        $userItemRepo = $this->doctrine->getRepository('NPSCoreBundle:UserItem');
        $laterItemRepo = $this->doctrine->getRepository('NPSCoreBundle:LaterItem');

        foreach ($items as $itemData) {
            $itemId = $itemData['item_id'];
            $labelId = $itemData['label_id'];
            $userItem = $userItemRepo->hasItem($userId, $itemId);
            if (!$userItem instanceof UserItem) {
                continue;
            }

            $laterItem = $laterItemRepo->laterExists($labelId, $userItem->getId());
            if ($laterItem instanceof LaterItem) {
                $laterItem->setUnread(true);
                $this->entityManager->persist($laterItem);

                continue;
            }

            $this->laterItem->addLaterItem($userItem, $labelId);
        }
        $this->entityManager->flush();
    }

    /**
     * Create new Label
     *
     * @param User $user
     * @param $name
     * @param null $dateUp
     *
     * @return Label
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
}
