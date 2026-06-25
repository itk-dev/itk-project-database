<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use ITKDev\EntityBundle\Attribute\ITKDevEntity;

/**
 * Base class for the app's domain entities. Carries #[ITKDevEntity] — the marker
 * the bundle scans for, inherited along the parent chain — while keeping the
 * project's own auto-increment id. ULID ids via the bundle's own
 * AbstractITKDevEntity are reserved for the dedicated feature branch.
 */
#[ORM\MappedSuperclass]
#[ITKDevEntity]
abstract class AbstractEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
