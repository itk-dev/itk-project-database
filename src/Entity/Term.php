<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\Vocabulary;
use App\Repository\TermRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TermRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_term_name_vocabulary', columns: ['name', 'vocabulary'])]
#[ApiResource(
    operations: [new GetCollection(), new Get()],
    normalizationContext: ['groups' => ['term:read']],
    paginationItemsPerPage: 50,
    order: ['name' => 'ASC'],
)]
#[ApiFilter(SearchFilter::class, properties: ['vocabulary' => 'exact', 'name' => 'partial'])]
#[ApiFilter(OrderFilter::class, properties: ['name'])]
class Term
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['term:read', 'initiative:read'])]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(length: 255)]
    #[Groups(['term:read', 'initiative:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 32, enumType: Vocabulary::class)]
    #[Groups(['term:read'])]
    private Vocabulary $vocabulary;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(Vocabulary $vocabulary = Vocabulary::Tag)
    {
        $this->vocabulary = $vocabulary;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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
