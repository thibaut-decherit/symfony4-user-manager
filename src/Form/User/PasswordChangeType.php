<?php

namespace App\Form\User;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class PasswordChangeType
 * @package App\Form\User
 */
class PasswordChangeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'user.current_password',
                'mapped' => false,
                'constraints' => [
                    new Length([
                        'max' => 4096,
                        'groups' => ['Password_Length']
                    ]),
                    new NotBlank([
                        'message' => 'form_errors.global.not_blank',
                        'groups' => ['Password_Blank']
                    ]),
                    new UserPassword([
                        'message' => 'form_errors.user.wrong_password',
                        'groups' => ['Password_Change']
                    ])
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'form_errors.user.repeat_password',
                'first_options' => [
                    'label' => 'user.new_password'
                ],
                'second_options' => [
                    'label' => 'user.new_password_repeat'
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
            /*
             * GroupSequence will validate constraints sequentially by iterating through the array, it means that if
             * password length validation fails, length error will be shown and validation will stop there.
             * UserPassword validation will not be triggered, thus preventing potential server load (or even DoS?)
             * if a very long password is being hashed.
             */
            'validation_groups' => new GroupSequence([
                'Password_Length',
                'Password_Blank',
                'Password_Change'
            ])
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
