<?php

namespace NPS\FrontendBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use NPS\CoreBundle\Entity\Filter;
use NPS\CoreBundle\Services\Entity\FeedService,
    NPS\CoreBundle\Services\Entity\LaterService;

/**
 * Type for a feed edit profile form
 */
class FilterEditType extends AbstractType
{
    /**
     * @var bool
     */
    private $created = false;

    /**
     * @var FeedService
     */
    private $feed;

    /**
     * @var array
     */
    private $filters;

    /**
     * @var LaterService
     */
    private $laterService;

    /**
     * @param array        $filters      defined array of filters
     * @param FeedService  $feed         FeedService
     * @param LaterService $laterService LaterService
     */
    public function __construct(array $filters, FeedService $feed, LaterService $laterService)
    {
        $this->filters      = $filters;
        $this->feed         = $feed;
        $this->laterService = $laterService;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            ['data_class' => 'NPS\CoreBundle\Entity\Filter']
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
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($builder) {
                $data = $event->getData();
                if ($data instanceof Filter) {
                    if ($data->getId()) {
                        $this->created = true;
                    }
                }
            }
        );


        $builder
            ->add(
                'name',
                null,
                array(
                    'label'    => 'Name',
                    'required' => true
                )
            )
            ->add(
                'type',
                ChoiceType::class,
                array(
                    'choices'  => $this->filters,
                    'multiple' => false,
                    'required' => true,
                )
            )
            ->add(
                'feeds',
                EntityType::class,
                array(
                    'class'         => 'NPSCoreBundle:Feed',
                    'query_builder' => $this->feed->getUserActiveFeedsQuery(),
                    'required'      => true,
                    'multiple'      => true,
                    'by_reference'  => false,
                )
            )
            ->add(
                'later',
                EntityType::class,
                array(
                    'class'         => 'NPSCoreBundle:Later',
                    'query_builder' => $this->laterService->getUserLabelsQuery(),
                    'required'      => false,
                    'multiple'      => false,
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
        return 'editFilter';
    }
}
