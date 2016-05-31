<?php

namespace NPS\FrontendBundle\Form\Type;

use NPS\CoreBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class SignInType
 *
 * @package NPS\FrontendBundle\Form\Type
 */
class SignInType extends AbstractType
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
                    'required' => true
                )
            )
            ->add(
                'password',
                PasswordType::class,
                array(
                    'required' => true
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
        return 'signIn';
    }
}
