<?php

namespace NPS\FrontendBundle\Form\Type;

use NPS\CoreBundle\Entity\Preference;
use NPS\CoreBundle\Services\Entity\LaterService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class PreferenceEditType
 *
 * @package NPS\FrontendBundle\Form\Type
 */
class PreferenceEditType extends AbstractType
{
    /** @var LaterService */
    private $laterService;

    /**
     * PreferenceEditType constructor.
     *
     * @param LaterService $laterService
     */
    public function __construct(LaterService $laterService)
    {
        $this->laterService = $laterService;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Preference::class,
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
        $builder->add(
            'sharedLater',
            EntityType::class,
            array(
                'class'         => 'NPSCoreBundle:Later',
                'query_builder' => $this->laterService->getUserLabelsQuery(),
                'label'         => '_Edit_shared_later',
                'required'      => true
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
        return 'editPreference';
    }
}
