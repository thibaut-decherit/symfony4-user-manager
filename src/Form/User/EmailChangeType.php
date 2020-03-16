<?php

namespace App\Form\User;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class EmailChangeType
 * @package App\Form\User
 */
class EmailChangeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('emailChangePending', EmailType::class, [
                'label' => 'user.email_address',
                'data' => '',
                'required' => false,
                'attr' => [
                    /**
                     * Will throw a missing translation error (false positive) in the profiler because Symfony always
                     * attempts to translate label and placeholder values.
                     */
                    'placeholder' => $builder->getData()->getEmail()
                ]
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => [
                'Email_Change'
            ]
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'App_user';
    }
}
