<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\Category;
use App\Enum\InitiativeType as InitiativeTypeEnum;
use App\Enum\OrganizationalAnchoring;
use App\Enum\Status;
use App\Model\InitiativeFilter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<InitiativeFilter>
 */
class InitiativeFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $boolChoiceValue = static fn (?bool $value): string => null === $value ? '' : ($value ? '1' : '0');

        $builder
            ->add('q', SearchType::class, [
                'label' => 'filter.search',
                'required' => false,
                'attr' => ['placeholder' => 'filter.search_placeholder'],
            ])
            ->add('status', EnumType::class, [
                'label' => 'initiative.status',
                'class' => Status::class,
                'required' => false,
                'placeholder' => 'filter.all',
                'choice_label' => static fn (Status $value): string => $value->labelKey(),
            ])
            ->add('category', EnumType::class, [
                'label' => 'initiative.category',
                'class' => Category::class,
                'required' => false,
                'placeholder' => 'filter.all',
                'choice_label' => static fn (Category $value): string => $value->labelKey(),
            ])
            ->add('initiativeType', EnumType::class, [
                'label' => 'initiative.initiative_type',
                'class' => InitiativeTypeEnum::class,
                'required' => false,
                'placeholder' => 'filter.all',
                'choice_label' => static fn (InitiativeTypeEnum $value): string => $value->labelKey(),
            ])
            ->add('organizationalAnchoring', EnumType::class, [
                'label' => 'initiative.organizational_anchoring',
                'class' => OrganizationalAnchoring::class,
                'required' => false,
                'placeholder' => 'filter.all',
                'choice_label' => static fn (OrganizationalAnchoring $value): string => $value->labelKey(),
            ])
            ->add('endorsement', ChoiceType::class, [
                'label' => 'initiative.endorsement',
                'required' => false,
                'placeholder' => 'filter.all',
                'choices' => ['filter.yes' => true, 'filter.no' => false],
                'choice_value' => $boolChoiceValue,
            ])
            ->add('published', ChoiceType::class, [
                'label' => 'initiative.published',
                'required' => false,
                'placeholder' => 'filter.all',
                'choices' => ['filter.published' => true, 'filter.draft' => false],
                'choice_value' => $boolChoiceValue,
            ])
            ->add('budgetMin', IntegerType::class, [
                'label' => 'filter.budget_min',
                'required' => false,
                'attr' => ['min' => 0],
            ])
            ->add('budgetMax', IntegerType::class, [
                'label' => 'filter.budget_max',
                'required' => false,
                'attr' => ['min' => 0],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InitiativeFilter::class,
            'method' => 'GET',
            'csrf_protection' => false,
            'required' => false,
            // The list also carries sort/direction/page query params that are
            // not form fields; ignore them instead of failing validation.
            'allow_extra_fields' => true,
        ]);
    }

    public function getBlockPrefix(): string
    {
        // Empty prefix keeps the query string clean (?status=…&q=…).
        return '';
    }
}
