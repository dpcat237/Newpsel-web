<?php
namespace NPS\CoreBundle\Services\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use NPS\CoreBundle\Entity\Feed;
use NPS\CoreBundle\Entity\Filter;
use NPS\CoreBundle\Entity\FilterFeed;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use NPS\FrontendBundle\Services\SystemNotificationService,
    NPS\CoreBundle\Services\UserWrapper;
use NPS\CoreBundle\Entity\Later;

/**
 * FilterService
 */
class FilterService
{
    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var Form
     */
    private $formData;

    /**
     * @var bool
     */
    private $formError = false;

    /**
     * @var Form
     */
    private $form;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @param Registry                  $doctrine           Registry
     * @param Logger                    $logger             Logger
     * @param SystemNotificationService $systemNotification SystemNotificationService
     * @param UserWrapper               $userWrapper        UserWrapper
     * @param Translator                $translator         Translator
     */
    public function __construct(Registry $doctrine, Logger $logger, SystemNotificationService $systemNotification, UserWrapper $userWrapper, Translator $translator)
    {

        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->logger = $logger;
        $this->systemNotification = $systemNotification;
        $this->userWrapper = $userWrapper;
        $this->translator = $translator;
    }

    /**
     * Process to validate filter form and create it
     *
     * @param Form $form
     */
    public function createFilter(Form $form)
    {
        $this->form = $form;
        $this->formData = $form->getData();
        $this->checkFilterForm();

        if ($this->formError) {
            return;
        }

        $this->filter = null;
        $this->createFilterObject();
        $this->entityManager->flush();
    }

    /**
     * Create filter in principal data base
     */
    private function createFilterObject()
    {
        $filter = new Filter();
        $filter->setType($this->formData->getType());
        $filter->setUser($this->userWrapper->getCurrentUser());
        $filter->setName($this->formData->getName());
        $filter->setLater($this->formData->getLater());
        foreach ($this->formData->getFeeds() as $feed) {
            $filter->addFeed($feed);
        }

        $this->entityManager->persist($filter);
        $this->filter = $filter;
    }

    /**
     * Check form for different filters
     */
    private function checkFilterForm()
    {
        $this->formError = false;
        switch ($this->formData->getType()) {
            case 'to.label':
                $this->checkFormToLabel();
                break;
        }
    }

    /**
     * Check that are all required data for filter "to.label"
     */
    private function checkFormToLabel()
    {
        if (!$this->formData->getLater() instanceof Later) {
            $error = new FormError($this->translator->trans('_Error_select_label'));
            $this->form->get('later')->addError($error);
            $this->formError = true;
        }
    }

    /**
     * Process to validate filter form and edit it
     *
     * @param Filter $filter
     * @param Form   $form
     */
    public function editFilter(Filter $filter, Form $form)
    {
        $this->form = $form;
        $this->formData = $form->getData();
        $this->checkFilterForm();

        if ($this->formError) {
            return;
        }

        $this->filter = $filter;
        $this->editFilterObject();
        $this->entityManager->flush();
    }

    /**
     * Create filter in principal data base
     *
     * @return boolean
     */
    private function editFilterObject()
    {
        if ($this->filter->getName() != $this->formData->getName()) {
            $this->filter->setName($this->formData->getName());
        }

        if ($this->filter->getLater()->getId() != $this->formData->getLater()->getId()) {
            $this->filter->setLater($this->formData->getLater());
        }
    }

    /**
     * Remove user's filter
     *
     * @param Filter $filter
     */
    public function removeFilter(Filter $filter)
    {
        foreach ($filter->getFilterFeeds() as $filterFeed) {
            $this->removeObject($filterFeed);
        }

        $this->removeObject($filter);
    }
}
