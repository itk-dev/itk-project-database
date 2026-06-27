<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Bridges a comma-separated text input and a collection of {@see Contact}s:
 * existing people are matched on name (case-insensitive) and pooled, while a
 * typed-in name creates a new contact on the fly — the same free-tagging
 * experience as the term fields.
 *
 * @implements DataTransformerInterface<mixed, mixed>
 */
final readonly class ContactsTextTransformer implements DataTransformerInterface
{
    public function __construct(private ContactRepository $contactRepository)
    {
    }

    public function transform(mixed $value): string
    {
        if (!is_iterable($value)) {
            return '';
        }

        $names = [];
        foreach ($value as $contact) {
            if ($contact instanceof Contact) {
                $names[] = $contact->getName();
            }
        }

        return implode(', ', $names);
    }

    /**
     * @return Collection<int, Contact>
     */
    public function reverseTransform(mixed $value): Collection
    {
        $contacts = new ArrayCollection();

        if (!\is_string($value) || '' === trim($value)) {
            return $contacts;
        }

        $seen = [];
        foreach (explode(',', $value) as $name) {
            $name = trim($name);
            $key = mb_strtolower($name);
            if ('' === $name || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $contacts->add($this->contactRepository->findOrCreate($name));
        }

        return $contacts;
    }
}
