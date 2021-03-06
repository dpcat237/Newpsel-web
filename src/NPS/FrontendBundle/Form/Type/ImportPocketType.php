<?php

namespace NPS\FrontendBundle\Form\Type;

use NPS\CoreBundle\Constant\ImportConstants;
use NPS\CoreBundle\Services\Entity\LaterService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Form to specify the import of later items from GetPocket
 */
class ImportPocketType extends AbstractType
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
            ->add('tag', null, array(
                'label' => '_Tag',
                'required' => false
            ))
            ->add('favorite', ChoiceType::class, array(
                'label' => '_Favorite',
                'choices'   => array(
                    ImportConstants::FAVORITE_ALL => '_All',
                    ImportConstants::FAVORITE_YES => '_Favorite',
                    ImportConstants::FAVORITE_NOT => '_Not_favorite',
                ),
                'data' => ImportConstants::FAVORITE_ALL,
                'empty_value' => false,
                'required' => false
            ))
            ->add('contentType', ChoiceType::class, array(
                'label' => '_Content_type',
                'choices'   => array(
                    ImportConstants::CONTENT_ALL => '_All',
                    ImportConstants::CONTENT_ARTICLE => '_Article',
                    ImportConstants::CONTENT_VIDEO => '_Video',
                ),
                'data' => ImportConstants::CONTENT_ALL,
                'empty_value' => false,
                'required' => false
            ))
            ->add('later', EntityType::class, array(
                'class' => 'NPSCoreBundle:Later',
                'query_builder' => $this->laterService->getUserLabelsQuery(),
                'required' => true,
                'multiple' => false,
            ));
    }

    /**
     * Return unique name for this form
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'pocket_import';
    }
}
