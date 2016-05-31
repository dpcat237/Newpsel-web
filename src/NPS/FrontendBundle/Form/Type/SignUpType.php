<?php

namespace NPS\FrontendBundle\Form\Type;

use NPS\CoreBundle\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Type for a feed edit profile form
 */
class SignUpType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => User::class,
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
        $builder
            ->add(
                'email',
                EmailType::class,
                array(
                    'label' => '_Your_email'
                )
            )
            ->add(
                'password',
                RepeatedType::class,
                array(
                    'type'            => 'password',
                    'invalid_message' => '_passwords_match',
                    'options'         => array('attr' => array('class' => 'password-field')),
                    'required'        => true,
                    'first_options'   => array('label' => '_Password'),
                    'second_options'  => array('label' => '_Confirm_password'),
                )
            )
            ->add('enabled', HiddenType::class, array('data' => 1));
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addConstraint(
            new UniqueEntity(
                array(
                    'fields'  => 'email',
                    'message' => '_Email_exists',
                )
            )
        );

        $metadata->addPropertyConstraint('email', new Assert\Email());
    }

    /**
     * Return unique name for this form
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'signUp';
    }
}
