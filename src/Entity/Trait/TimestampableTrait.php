<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Adds created/updated timestamps populated by {@see \App\EventListener\TimestampableListener}
 * on flush. The timestampable:read group lets a resource opt the timestamps into
 * its API normalization without the trait knowing about any resource.
 */
trait TimestampableTrait
{
    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['timestampable:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['timestampable:read'])]
    private \DateTimeImmutable $updatedAt;

    // The listener fills these on first flush, so they may be uninitialized between
    // `new Entity()` and `$em->flush()`. `?? null` is safe on uninitialized typed
    // properties (PHP 8+) and lets callers null-check before flush.
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt ?? null;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt ?? null;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
