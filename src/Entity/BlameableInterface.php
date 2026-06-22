<?php

declare(strict_types=1);

namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Marks an entity whose creator/modifier {@see \App\EventListener\BlameableListener} maintains.
 */
interface BlameableInterface
{
    public function getCreatedBy(): ?UserInterface;

    public function getModifiedBy(): ?UserInterface;

    public function setCreatedBy(?UserInterface $user): void;

    public function setModifiedBy(?UserInterface $user): void;
}
