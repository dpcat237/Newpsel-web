<?php

namespace NPS\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Type for a feed edit profile form
 */
class PreferenceEditType extends AbstractType
{
    /**
     * @var string
     */
    private $labelsQuery;

    /**
     * Constructor
     *
     * @param string $labelsQuery user labels query
     */
    public function __construct($labelsQuery)
    {
        $this->labelsQuery = $labelsQuery;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'NPS\CoreBundle\Entity\Preference',
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
        $builder->add('sharedLater', 'entity', array(
            'class' => 'NPSCoreBundle:Later',
            'query_builder' => $this->labelsQuery,
            'label' => '_Edit_shared_later',
            'required' => true)
        );
    }

    /**
     * Return unique name for this form
     *
     * @return string
     */
    public function getName()
    {
        return 'editPreference';
    }
}