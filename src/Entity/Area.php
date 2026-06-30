<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AreaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AreaRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'area.name_duplicate')]
class Area extends AbstractEntity
{
    #[Assert\NotBlank]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
