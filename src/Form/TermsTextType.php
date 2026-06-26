<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\Vocabulary;
use App\Form\DataTransformer\TermsTextTransformer;
use App\Repository\TermRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A text input mapping a comma-separated list of names to a collection of
 * free-tagging {@see \App\Entity\Term}s. Pass the target `vocabulary`.
 *
 * @extends AbstractType<mixed>
 */
final class TermsTextType extends AbstractType
{
    public function __construct(private readonly TermRepository $termRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new TermsTextTransformer($this->termRepository, $options['vocabulary']));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('vocabulary');
        $resolver->setAllowedTypes('vocabulary', Vocabulary::class);
        $resolver->setDefaults([
            'invalid_message' => 'form.terms.invalid',
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
