<?php

namespace NPS\FrontendBundle\Services\Entity;

use NPS\CoreBundle\Entity\Filter;
use NPS\CoreBundle\Entity\Later;
use NPS\CoreBundle\Services\Entity\FilterService;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;

/**
 * Class FilterFrontendService
 *
 * @package NPS\FrontendBundle\Services\Entity
 */
class FilterFrontendService extends AbstractEntityFrontendService
{
    /** @var Filter */
    protected $filter;

    /** @var Form */
    protected $formData;

    /** @var bool */
    protected $formError = false;

    /** @var Form */
    protected $form;

    /** @var FilterService */
    protected $filterService;

    /**
     * @param FilterService $filterService
     */
    public function setFilterService(FilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    /**
     * Process to validate filter form and create it
     *
     * @param Form $form
     */
    public function createFilter(Form $form)
    {
        $this->form     = $form;
        $this->formData = $form->getData();
        $this->checkFilterForm();

        if ($this->formError) {
            return;
        }

        $this->filter = null;
        $this->filterService->createFilterFeeds(
            $this->userWrapper->getCurrentUser(),
            $this->formData->getType(),
            $this->formData->getName(),
            $this->formData->getLater(),
            $this->formData->getFeeds()
        );
    }

    /**
     * Check form for different filters
     */
    private function checkFilterForm()
    {
        $this->formError = false;
        switch ($this->formData->getType()) {
            case Filter::FILTER_FEED_TO_TAG:
                $this->checkFormToLabel();
                break;
        }
    }

    /**
     * Check that are all required data for filter Filter::FILTER_FEED_TO_TAG
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
        $this->form     = $form;
        $this->formData = $form->getData();
        $this->checkFilterForm();

        if ($this->formError) {
            return;
        }

        if ($filter->nameIsDifferent($this->formData->getName())) {
            $filter->setName($this->formData->getName());
        }

        if ($filter->tagIsDifferent($this->formData->getLater()->getId())) {
            $filter->setLater($this->formData->getLater());
        }

        $this->filterService->saveObject($filter);
    }

    /**
     * @inheritdoc
     */
    public function removeFilter(Filter $filter)
    {
        $this->filterService->removeFilter($filter);
    }
}
