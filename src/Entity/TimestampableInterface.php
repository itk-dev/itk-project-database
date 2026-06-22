<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Marks an entity whose timestamps {@see \App\EventListener\TimestampableListener} maintains.
 */
interface TimestampableInterface
{
    public function getCreatedAt(): ?\DateTimeImmutable;

    public function getUpdatedAt(): ?\DateTimeImmutable;

    public function setCreatedAt(\DateTimeImmutable $createdAt): void;

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void;
}
