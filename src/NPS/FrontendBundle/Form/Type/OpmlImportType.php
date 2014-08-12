<?php

namespace NPS\FrontendBundle\Form\Type;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Type for a feed edit profile form
 */
class OpmlImportType extends AbstractType
{
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

    /**
     * Buildform function
     *
     * @param FormBuilderInterface $builder the formBuilder
     * @param array                $options the options for this form
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('opml_file', 'file', array(
            'required' => true,
        ));
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('opml_file', new Assert\File(array(
            'maxSize' => '1024k',
            'mimeTypes' => array(
                'text/x-opml+xml'
            ),
            'mimeTypesMessage' => '_Invalid_opml',
        )));
    }

    /**
     * Return unique name for this form
     *
     * @return string
     */
    public function getName()
    {
        return 'opml_import';
    }
}