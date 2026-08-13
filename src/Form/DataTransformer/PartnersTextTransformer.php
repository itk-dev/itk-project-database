<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\Partner;
use App\Repository\PartnerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Bridges a comma-separated text input and a collection of {@see Partner}s:
 * existing partners are matched on name (case-insensitive) and pooled, while a
 * typed-in name creates a new partner on the fly — the same free-tagging
 * experience as the contact and term fields.
 *
 * @implements DataTransformerInterface<mixed, mixed>
 */
final readonly class PartnersTextTransformer implements DataTransformerInterface
{
    public function __construct(private PartnerRepository $partnerRepository)
    {
    }

    public function transform(mixed $value): string
    {
        if (!is_iterable($value)) {
            return '';
        }

        $names = [];
        foreach ($value as $partner) {
            if ($partner instanceof Partner) {
                $names[] = $partner->getName();
            }
        }

        return implode(', ', $names);
    }

    /**
     * @return Collection<int, Partner>
     */
    public function reverseTransform(mixed $value): Collection
    {
        $partners = new ArrayCollection();

        if (!\is_string($value) || '' === trim($value)) {
            return $partners;
        }

        $seen = [];
        foreach (explode(',', $value) as $name) {
            $name = trim($name);
            $key = mb_strtolower($name);
            if ('' === $name || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $partners->add($this->partnerRepository->findOrCreate($name));
        }

        return $partners;
    }
}
