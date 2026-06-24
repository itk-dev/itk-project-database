<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<User>
 */
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'user.email',
            ])
            ->add('name', TextType::class, [
                'label' => 'user.name',
                'required' => false,
            ])
            // Mapped onto User::$roles, so this field can grant ROLE_ADMIN. It
            // relies on UserController being gated by #[IsGranted('ROLE_ADMIN')];
            // do not reuse this form for self-service profile editing without
            // removing this field. The Choice constraint limits submissions to
            // the known roles so a tampered request cannot inject arbitrary ones.
            ->add('roles', ChoiceType::class, [
                'label' => 'user.roles',
                'choices' => [
                    'role.user' => 'ROLE_USER',
                    'role.admin' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
                'constraints' => [
                    new Choice(choices: ['ROLE_USER', 'ROLE_ADMIN'], multiple: true),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'user.password',
                'mapped' => false,
                'required' => $options['require_password'],
                'constraints' => $options['require_password'] ? [
                    new NotBlank(message: 'user.password_required'),
                    new Length(min: 8, minMessage: 'user.password_too_short'),
                ] : [],
                'help' => 'user.password_help',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'require_password' => true,
        ]);
        $resolver->setAllowedTypes('require_password', 'bool');
    }
}
