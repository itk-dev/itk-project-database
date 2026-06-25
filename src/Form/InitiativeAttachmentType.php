<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\InitiativeAttachment;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<InitiativeAttachment>
 */
class InitiativeAttachmentType extends AbstractType
{
    public function __construct(
        #[Autowire('%env(INITIATIVE_ATTACHMENT_MAX_SIZE)%')]
        private readonly string $maxFileSize,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'initiative.attachment_file',
                'required' => false,
                'attr' => ['accept' => '.pdf,.doc,.docx,.xls,.xlsx'],
                'constraints' => [
                    new Assert\File(
                        maxSize: $this->maxFileSize,
                        mimeTypes: [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ],
                        mimeTypesMessage: 'initiative.attachment_invalid_type',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InitiativeAttachment::class,
        ]);
    }
}
