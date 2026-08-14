<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Area;
use App\Entity\Department;
use App\Entity\Initiative;
use App\Enum\EndorsementAuthor;
use App\Enum\Funding;
use App\Enum\InitiativeType as InitiativeTypeEnum;
use App\Enum\Status;
use App\Enum\Vocabulary;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

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
                'help' => 'initiative.title_help',
            ])
            ->add('topic', TextareaType::class, [
                'label' => 'initiative.topic',
                'required' => false,
                'attr' => ['rows' => 4],
                'help' => 'initiative.topic_help',
            ])
            ->add('area', EntityType::class, [
                'label' => 'initiative.area',
                'class' => Area::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'form.choose',
                'help' => 'initiative.area_help',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'initiative.description',
                'required' => false,
                'attr' => ['rows' => 4],
                'help' => 'initiative.description_help',
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
                'help' => 'initiative.initiative_type_help',
            ])
            ->add('status', EnumType::class, [
                'label' => 'initiative.status',
                'class' => Status::class,
                'required' => false,
                'placeholder' => 'form.choose',
                'choice_label' => static fn (Status $value): string => $value->labelKey(),
                'help' => 'initiative.status_help',
            ])
            ->add('statusAdditional', TextareaType::class, [
                'label' => 'initiative.status_additional',
                'required' => false,
                'attr' => ['rows' => 3],
                'help' => 'initiative.status_additional_help',
            ])
            ->add('organizationalAnchoring', EntityType::class, [
                'label' => 'initiative.organizational_anchoring',
                'class' => Department::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'form.choose',
                'help' => 'initiative.organizational_anchoring_help',
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
                'help' => 'initiative.endorsement_author_help',
            ])
            ->add('budget', IntegerType::class, [
                'label' => 'initiative.budget',
                'required' => false,
                'attr' => ['min' => 0],
                'help' => 'initiative.budget_help',
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
                'help' => 'initiative.time_period_start_help',
            ])
            ->add('timePeriodEnd', DateType::class, [
                'label' => 'initiative.time_period_end',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'help' => 'initiative.time_period_end_help',
            ])
            ->add('links', CollectionType::class, [
                'label' => 'initiative.links',
                'entry_type' => UrlType::class,
                'entry_options' => [
                    'required' => false,
                    'default_protocol' => 'https',
                    'label' => false,
                    // Reject non-http(s) URLs (e.g. javascript:) to prevent stored XSS.
                    'constraints' => [new Assert\Url(protocols: ['http', 'https'])],
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
                'required' => false,
                'prototype' => true,
            ])
            ->add('contacts', ContactsTextType::class, [
                'label' => 'initiative.contacts',
                'required' => false,
                'help' => 'initiative.terms_help',
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
            ]);
    }

    /**
     * Flag the fields that count towards {@see Initiative::getCompletionPercentage()}
     * so the form theme can mark them. Keeps the list in one place.
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        foreach (Initiative::COMPLETION_FIELDS as $field) {
            if (isset($view[$field])) {
                $view[$field]->vars['completion_field'] = true;
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Initiative::class,
        ]);
    }
}
