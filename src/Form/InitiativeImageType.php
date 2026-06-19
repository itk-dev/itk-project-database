<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\InitiativeImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<InitiativeImage>
 */
class InitiativeImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('imageFile', FileType::class, [
                'label' => 'initiative.image_file',
                'required' => false,
                'attr' => ['accept' => 'image/png,image/jpeg,image/gif'],
            ])
            ->add('alt', TextType::class, [
                'label' => 'initiative.image_alt',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InitiativeImage::class,
        ]);
    }
}
