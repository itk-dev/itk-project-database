<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Vocabulary;
use App\Repository\TermRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TermRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_term_name_vocabulary', columns: ['name', 'vocabulary'])]
class Term extends AbstractEntity
{
    #[Assert\NotBlank]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 32, enumType: Vocabulary::class)]
    private Vocabulary $vocabulary;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(Vocabulary $vocabulary = Vocabulary::Tag)
    {
        $this->vocabulary = $vocabulary;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getVocabulary(): Vocabulary
    {
        return $this->vocabulary;
    }

    public function setVocabulary(Vocabulary $vocabulary): static
    {
        $this->vocabulary = $vocabulary;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
