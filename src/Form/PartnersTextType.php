<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Partner;
use App\Form\DataTransformer\PartnersTextTransformer;
use App\Repository\PartnerRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A text input mapping a comma-separated list of names to a collection of
 * {@see Partner}s, picked from the shared pool or created on the fly.
 *
 * @extends AbstractType<mixed>
 */
final class PartnersTextType extends AbstractType
{
    public function __construct(private readonly PartnerRepository $partnerRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new PartnersTextTransformer($this->partnerRepository));
    }

    /**
     * Expose the existing partners so the client can offer them as a searchable
     * pool (and let new ones join it). The names are rendered as a JSON data
     * attribute the Tom Select initialiser reads.
     *
     * A required field also gets the autosave marker, so the client holds back an
     * initiative that has no partner yet instead of posting it and reporting a
     * save error.
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $pool = array_map(
            static fn (Partner $partner): string => (string) $partner->getName(),
            $this->partnerRepository->findAllOrdered(),
        );

        $attr = [
            'data-partner-select' => '',
            'data-partner-pool' => json_encode($pool, \JSON_THROW_ON_ERROR),
        ];

        if ($options['required']) {
            $attr['data-autosave-required'] = 'true';
        }

        $view->vars['attr'] = array_merge($view->vars['attr'], $attr);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'invalid_message' => 'form.terms.invalid',
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
