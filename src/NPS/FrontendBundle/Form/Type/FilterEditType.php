<?php

namespace NPS\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use NPS\CoreBundle\Entity\Filter;

/**
 * Type for a feed edit profile form
 */
class FilterEditType extends AbstractType
{
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'NPS\CoreBundle\Entity\Filter',
        ));
    }

    /**
     * Buildform function
     *
     * @param FormBuilderInterface $builder the formBuilder
     * @param array                $options the options for this form
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', null, array('label' => 'Name', 'required' => true))
            ->add('type', 'choice', array(
                'choices'   =>  array(
                    Filter::TO_LABEL => '_Save_to_label',
                ),
                'multiple'  =>  false,
                'required'  =>  true,
            ));

    }

    /**
     * Return unique name for this form
     *
     * @return string
     */
    public function getName()
    {
        return 'editFilter';
    }
}