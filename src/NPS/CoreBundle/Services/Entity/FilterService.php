<?php
namespace NPS\CoreBundle\Services\Entity;

use NPS\CoreBundle\Entity\Filter,
    NPS\CoreBundle\Entity\Later;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Form\Form,
    Symfony\Component\Form\FormError;

/**
 * FilterService
 */
class FilterService extends AbstractEntityService
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
            $error = new FormError($this->notification->trans('_Error_select_label'));
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
