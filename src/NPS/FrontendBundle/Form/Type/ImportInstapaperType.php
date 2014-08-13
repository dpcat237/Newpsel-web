<?php

namespace NPS\FrontendBundle\Form\Type;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use NPS\CoreBundle\Services\Entity\LaterService;

/**
 * Form to specify the import of later items from Instapaper
 */
class ImportInstapaperType extends AbstractType
{
    /**
     * @var LaterService
     */
    private $laterService;


    /**
     * @param LaterService $laterService LaterService
     */
    public function __construct(LaterService $laterService)
    {
        $this->laterService = $laterService;
    }

    /**
     * Buildform function
     *
     * @param FormBuilderInterface $builder the formBuilder
     * @param array                $options the options for this form
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('later', 'entity', array(
                'class' => 'NPSCoreBundle:Later',
                'query_builder' => $this->laterService->getUserLabelsQuery(),
                'required' => true,
                'multiple' => false,
            ))
            ->add('csv_file', 'file', array(
                'required' => true,
            ));
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('csv_file', new Assert\File(array(
            'maxSize' => '10000k',
            'mimeTypes' => array(
                'text/csv'
            ),
            'mimeTypesMessage' => '_Invalid_csv',
        )));
    }

    /**
     * Return unique name for this form
     *
     * @return string
     */
    public function getName()
    {
        return 'instapaper_import';
    }
}