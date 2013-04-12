<?php

namespace NPS\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Type for a feed edit profile form
 */
class FeedAddType extends AbstractType
{
    /**
     * Default form options
     *
     * @param array $options
     *
     * @return array With the options
     */
    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'NPS\ModelBundle\Entity\Feed',
        );
    }

    /**
     * Buildform function
     *
     * @param FormBuilderInterface $builder the formBuilder
     * @param array                $options the options for this form
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('url', null, array('label' => 'Name', 'required' => true));
    }

    /**
     * Return unique name for this form
     *
     * @return string
     */
    public function getName()
    {
        return 'addFeed';
    }
}