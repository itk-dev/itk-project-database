<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Partner;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Partner>
 */
class PartnerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'partner.name',
                'help' => 'partner.name_help',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'partner.description',
                'required' => false,
                'attr' => ['rows' => 4],
                'help' => 'partner.description_help',
            ])
            ->add('website', UrlType::class, [
                'label' => 'partner.website',
                'required' => false,
                'default_protocol' => 'https',
                'help' => 'partner.website_help',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Partner::class,
        ]);
    }
}
