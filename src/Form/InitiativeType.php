<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Initiative;
use App\Enum\Category;
use App\Enum\EndorsementAuthor;
use App\Enum\Funding;
use App\Enum\InitiativeType as InitiativeTypeEnum;
use App\Enum\OrganizationalAnchoring;
use App\Enum\Status;
use App\Enum\Vocabulary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Initiative>
 */
class InitiativeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'initiative.title',
            ])
            ->add('category', EnumType::class, [
                'label' => 'initiative.category',
                'class' => Category::class,
                'required' => false,
                'placeholder' => 'form.choose',
                'choice_label' => static fn (Category $value): string => $value->labelKey(),
            ])
            ->add('description', TextareaType::class, [
                'label' => 'initiative.description',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('strategies', TermsTextType::class, [
                'label' => 'initiative.strategies',
                'vocabulary' => Vocabulary::Strategy,
                'required' => false,
                'help' => 'initiative.terms_help',
            ])
            ->add('initiativeType', EnumType::class, [
                'label' => 'initiative.initiative_type',
                'class' => InitiativeTypeEnum::class,
                'required' => false,
                'placeholder' => 'form.choose',
                'choice_label' => static fn (InitiativeTypeEnum $value): string => $value->labelKey(),
            ])
            ->add('status', EnumType::class, [
                'label' => 'initiative.status',
                'class' => Status::class,
                'required' => false,
                'placeholder' => 'form.choose',
                'choice_label' => static fn (Status $value): string => $value->labelKey(),
            ])
            ->add('statusAdditional', TextareaType::class, [
                'label' => 'initiative.status_additional',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('organizationalAnchoring', EnumType::class, [
                'label' => 'initiative.organizational_anchoring',
                'class' => OrganizationalAnchoring::class,
                'required' => false,
                'placeholder' => 'form.choose',
                'choice_label' => static fn (OrganizationalAnchoring $value): string => $value->labelKey(),
            ])
            ->add('endorsement', CheckboxType::class, [
                'label' => 'initiative.endorsement',
                'required' => false,
            ])
            ->add('endorsementAuthor', EnumType::class, [
                'label' => 'initiative.endorsement_author',
                'class' => EndorsementAuthor::class,
                'required' => false,
                'placeholder' => 'form.choose',
                'choice_label' => static fn (EndorsementAuthor $value): string => $value->labelKey(),
            ])
            ->add('budget', IntegerType::class, [
                'label' => 'initiative.budget',
                'required' => false,
                'attr' => ['min' => 0],
            ])
            ->add('funding', EnumType::class, [
                'label' => 'initiative.funding',
                'class' => Funding::class,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choice_label' => static fn (Funding $value): string => $value->labelKey(),
            ])
            ->add('stakeholders', TermsTextType::class, [
                'label' => 'initiative.stakeholders',
                'vocabulary' => Vocabulary::Stakeholder,
                'required' => false,
                'help' => 'initiative.terms_help',
            ])
            ->add('tags', TermsTextType::class, [
                'label' => 'initiative.tags',
                'vocabulary' => Vocabulary::Tag,
                'required' => false,
                'help' => 'initiative.terms_help',
            ])
            ->add('timePeriodStart', DateType::class, [
                'label' => 'initiative.time_period_start',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ])
            ->add('timePeriodEnd', DateType::class, [
                'label' => 'initiative.time_period_end',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ])
            ->add('links', CollectionType::class, [
                'label' => 'initiative.links',
                'entry_type' => UrlType::class,
                'entry_options' => [
                    'required' => false,
                    'default_protocol' => 'https',
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
                'required' => false,
                'prototype' => true,
            ])
            ->add('contacts', CollectionType::class, [
                'label' => 'initiative.contacts',
                'entry_type' => ContactType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'prototype' => true,
            ])
            ->add('images', CollectionType::class, [
                'label' => 'initiative.images',
                'entry_type' => InitiativeImageType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'prototype' => true,
            ])
            ->add('attachments', CollectionType::class, [
                'label' => 'initiative.attachments',
                'entry_type' => InitiativeAttachmentType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'prototype' => true,
            ])
            ->add('author', TextType::class, [
                'label' => 'initiative.author',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Initiative::class,
        ]);
    }
}
