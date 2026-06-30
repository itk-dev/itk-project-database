<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Contact;
use App\Form\DataTransformer\ContactsTextTransformer;
use App\Repository\ContactRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A text input mapping a comma-separated list of names to a collection of
 * {@see Contact}s, picked from the shared pool or created on the fly.
 *
 * @extends AbstractType<mixed>
 */
final class ContactsTextType extends AbstractType
{
    public function __construct(private readonly ContactRepository $contactRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new ContactsTextTransformer($this->contactRepository));
    }

    /**
     * Expose the existing contacts so the client can offer them as a searchable
     * pool (and let new ones join it). The names are rendered as a JSON data
     * attribute the Tom Select initialiser reads.
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $pool = array_map(
            static fn (Contact $contact): string => (string) $contact->getName(),
            $this->contactRepository->findAllOrdered(),
        );

        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-contact-select' => '',
            'data-contact-pool' => json_encode($pool, \JSON_THROW_ON_ERROR),
        ]);
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
