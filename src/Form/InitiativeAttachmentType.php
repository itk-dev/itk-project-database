<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\InitiativeAttachment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<InitiativeAttachment>
 */
class InitiativeAttachmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'initiative.attachment_file',
                'required' => false,
                'attr' => ['accept' => '.pdf,.doc,.docx,.xls,.xlsx'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InitiativeAttachment::class,
        ]);
    }
}
