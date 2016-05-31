<?php

namespace NPS\FrontendBundle\Form\Type;

use NPS\CoreBundle\Entity\Later;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class LaterEditType
 *
 * @package NPS\FrontendBundle\Form\Type
 */
class LaterEditType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Later::class,
            )
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
        $builder->add('name', null, array('label' => 'Name', 'required' => true));
    }

    /**
     * Return unique name for this form
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'editLater';
    }
}
