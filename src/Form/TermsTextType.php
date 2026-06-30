<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Term;
use App\Enum\Vocabulary;
use App\Form\DataTransformer\TermsTextTransformer;
use App\Repository\TermRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A text input mapping a comma-separated list of names to a collection of
 * free-tagging {@see Term}s. Pass the target `vocabulary`.
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

    /**
     * Expose the vocabulary's existing terms so the client can offer them as a
     * searchable pool (and let new ones join it). The names are rendered as a
     * JSON data attribute the Tom Select initialiser reads.
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $pool = array_map(
            static fn (Term $term): string => (string) $term->getName(),
            $this->termRepository->findByVocabulary($options['vocabulary']),
        );

        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-term-select' => '',
            'data-term-pool' => json_encode($pool, \JSON_THROW_ON_ERROR),
        ]);
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
