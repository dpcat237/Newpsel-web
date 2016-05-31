<?php

namespace NPS\FrontendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Form to import OPML files
 */
class ImportOpmlType extends AbstractType
{
    /**
     * Buildform function
     *
     * @param FormBuilderInterface $builder the formBuilder
     * @param array                $options the options for this form
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'opml_file',
            FileType::class,
            array(
                'required' => true,
            )
        );
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint(
            'opml_file',
            new Assert\File(
                array(
                    'maxSize'          => '1024k',
                    'mimeTypes'        => array(
                        'text/x-opml+xml'
                    ),
                    'mimeTypesMessage' => '_Invalid_opml',
                )
            )
        );
    }

    /**
     * Return unique name for this form
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'opml_import';
    }
}
