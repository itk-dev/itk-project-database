<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PartnerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PartnerRepository::class)]
#[ORM\Index(name: 'idx_partner_name', columns: ['name'])]
#[UniqueEntity(fields: ['name'], message: 'partner.name_duplicate')]
class Partner extends AbstractEntity
{
    public const int NAME_MAX_LENGTH = 255;

    /**
     * Duplicate names are kept out by {@see UniqueEntity} and by the
     * case-insensitive lookup in {@see PartnerRepository::findOrCreate()},
     * not by a unique index. Partners are created inline from the autosaving
     * initiative form, where lookup and insert are separate steps: a unique index
     * would turn two saves racing on the same new name into an uncaught driver
     * exception, where the worst this can do is leave two rows for an admin to
     * merge. Contact and Term, created the same way, are modelled the same.
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::NAME_MAX_LENGTH)]
    // Comma is the separator of the free-tagging field on the initiative form, so a
    // name containing one would be split into two partners on the next edit.
    #[Assert\Regex(pattern: '/,/', match: false, message: 'partner.name_comma')]
    #[ORM\Column(length: self::NAME_MAX_LENGTH)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    // Rejects non-http(s) URLs (e.g. javascript:) since the value is rendered as a link.
    #[Assert\Url(protocols: ['http', 'https'])]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
