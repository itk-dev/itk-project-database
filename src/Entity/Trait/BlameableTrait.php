<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Tracks which user created and last modified the entity. The columns are filled
 * by {@see \App\EventListener\BlameableListener} on flush, so callers never set
 * them by hand. UserInterface is resolved to the concrete User entity via
 * doctrine.orm.resolve_target_entities.
 */
trait BlameableTrait
{
    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?UserInterface $createdBy = null;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?UserInterface $modifiedBy = null;

    public function getCreatedBy(): ?UserInterface
    {
        return $this->createdBy;
    }

    public function getModifiedBy(): ?UserInterface
    {
        return $this->modifiedBy;
    }

    public function setCreatedBy(?UserInterface $user): void
    {
        $this->createdBy = $user;
    }

    public function setModifiedBy(?UserInterface $user): void
    {
        $this->modifiedBy = $user;
    }
}
