<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\EndorsementAuthor;
use App\Enum\Funding;
use App\Enum\InitiativeType;
use App\Enum\Status;
use App\Repository\InitiativeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InitiativeRepository::class)]
class Initiative extends AbstractEntity
{
    /**
     * Fields that count toward {@see getCompletionPercentage()} and the client-side
     * progress bar. Limited to the initiative's own columns so list rendering stays
     * query-free; the booleans and the relational lists are intentionally excluded.
     *
     * @var list<string>
     */
    public const array COMPLETION_FIELDS = [
        'title', 'area', 'description', 'initiativeType', 'status',
        'organizationalAnchoring',
        'budget', 'funding', 'timePeriodStart', 'timePeriodEnd',
    ];

    #[Assert\NotBlank]
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\ManyToOne(targetEntity: Area::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Area $area = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** @var Collection<int, Term> */
    #[ORM\ManyToMany(targetEntity: Term::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'initiative_strategy')]
    private Collection $strategies;

    #[ORM\Column(length: 32, nullable: true, enumType: InitiativeType::class)]
    private ?InitiativeType $initiativeType = null;

    #[ORM\Column(length: 32, nullable: true, enumType: Status::class)]
    private ?Status $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $statusAdditional = null;

    #[ORM\ManyToOne(targetEntity: Department::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Department $organizationalAnchoring = null;

    #[ORM\Column]
    private bool $endorsement = false;

    #[ORM\Column(length: 32, nullable: true, enumType: EndorsementAuthor::class)]
    private ?EndorsementAuthor $endorsementAuthor = null;

    /** @var Collection<int, Contact> */
    #[ORM\ManyToMany(targetEntity: Contact::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'initiative_contact')]
    private Collection $contacts;

    /** @var Collection<int, Partner> */
    #[Assert\Count(min: 1, minMessage: 'initiative.partners_required')]
    #[ORM\ManyToMany(targetEntity: Partner::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'initiative_partner')]
    private Collection $partners;

    /** @var Collection<int, InitiativeImage> */
    #[ORM\OneToMany(targetEntity: InitiativeImage::class, mappedBy: 'initiative', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $images;

    /** @var Collection<int, InitiativeAttachment> */
    #[ORM\OneToMany(targetEntity: InitiativeAttachment::class, mappedBy: 'initiative', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $attachments;

    /** @var Collection<int, Term> */
    #[ORM\ManyToMany(targetEntity: Term::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'initiative_stakeholder')]
    private Collection $stakeholders;

    #[Assert\PositiveOrZero]
    #[ORM\Column(nullable: true)]
    private ?int $budget = null;

    /**
     * Stored as the backing values of {@see Funding}; accessors expose enums.
     *
     * @var list<string>
     */
    #[ORM\Column]
    private array $funding = [];

    /** @var Collection<int, Term> */
    #[ORM\ManyToMany(targetEntity: Term::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'initiative_tag')]
    private Collection $tags;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $timePeriodStart = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $timePeriodEnd = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $links = [];

    public function __construct()
    {
        parent::__construct();
        $this->strategies = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->partners = new ArrayCollection();
        $this->stakeholders = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->attachments = new ArrayCollection();
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getArea(): ?Area
    {
        return $this->area;
    }

    public function setArea(?Area $area): static
    {
        $this->area = $area;

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

    /** @return Collection<int, Term> */
    public function getStrategies(): Collection
    {
        return $this->strategies;
    }

    public function addStrategy(Term $term): static
    {
        if (!$this->strategies->contains($term)) {
            $this->strategies->add($term);
        }

        return $this;
    }

    public function removeStrategy(Term $term): static
    {
        $this->strategies->removeElement($term);

        return $this;
    }

    /** @param iterable<Term> $terms */
    public function setStrategies(iterable $terms): static
    {
        $this->strategies->clear();
        foreach ($terms as $term) {
            $this->addStrategy($term);
        }

        return $this;
    }

    public function getInitiativeType(): ?InitiativeType
    {
        return $this->initiativeType;
    }

    public function setInitiativeType(?InitiativeType $initiativeType): static
    {
        $this->initiativeType = $initiativeType;

        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusAdditional(): ?string
    {
        return $this->statusAdditional;
    }

    public function setStatusAdditional(?string $statusAdditional): static
    {
        $this->statusAdditional = $statusAdditional;

        return $this;
    }

    public function getOrganizationalAnchoring(): ?Department
    {
        return $this->organizationalAnchoring;
    }

    public function setOrganizationalAnchoring(?Department $organizationalAnchoring): static
    {
        $this->organizationalAnchoring = $organizationalAnchoring;

        return $this;
    }

    public function isEndorsement(): bool
    {
        return $this->endorsement;
    }

    public function setEndorsement(bool $endorsement): static
    {
        $this->endorsement = $endorsement;

        return $this;
    }

    public function getEndorsementAuthor(): ?EndorsementAuthor
    {
        return $this->endorsementAuthor;
    }

    public function setEndorsementAuthor(?EndorsementAuthor $endorsementAuthor): static
    {
        $this->endorsementAuthor = $endorsementAuthor;

        return $this;
    }

    /** @return Collection<int, Contact> */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(Contact $contact): static
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
        }

        return $this;
    }

    public function removeContact(Contact $contact): static
    {
        $this->contacts->removeElement($contact);

        return $this;
    }

    /** @return Collection<int, Partner> */
    public function getPartners(): Collection
    {
        return $this->partners;
    }

    public function addPartner(Partner $partner): static
    {
        if (!$this->partners->contains($partner)) {
            $this->partners->add($partner);
        }

        return $this;
    }

    public function removePartner(Partner $partner): static
    {
        $this->partners->removeElement($partner);

        return $this;
    }

    /** @param iterable<Partner> $partners */
    public function setPartners(iterable $partners): static
    {
        $this->partners->clear();
        foreach ($partners as $partner) {
            $this->addPartner($partner);
        }

        return $this;
    }

    /** @return Collection<int, InitiativeImage> */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(InitiativeImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setInitiative($this);
        }

        return $this;
    }

    public function removeImage(InitiativeImage $image): static
    {
        $this->images->removeElement($image);

        return $this;
    }

    /** @return Collection<int, InitiativeAttachment> */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(InitiativeAttachment $attachment): static
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setInitiative($this);
        }

        return $this;
    }

    public function removeAttachment(InitiativeAttachment $attachment): static
    {
        $this->attachments->removeElement($attachment);

        return $this;
    }

    /** @return Collection<int, Term> */
    public function getStakeholders(): Collection
    {
        return $this->stakeholders;
    }

    public function addStakeholder(Term $term): static
    {
        if (!$this->stakeholders->contains($term)) {
            $this->stakeholders->add($term);
        }

        return $this;
    }

    public function removeStakeholder(Term $term): static
    {
        $this->stakeholders->removeElement($term);

        return $this;
    }

    /** @param iterable<Term> $terms */
    public function setStakeholders(iterable $terms): static
    {
        $this->stakeholders->clear();
        foreach ($terms as $term) {
            $this->addStakeholder($term);
        }

        return $this;
    }

    public function getBudget(): ?int
    {
        return $this->budget;
    }

    public function setBudget(?int $budget): static
    {
        $this->budget = $budget;

        return $this;
    }

    /** @return Funding[] */
    public function getFunding(): array
    {
        return array_values(array_filter(array_map(
            static fn (string $value): ?Funding => Funding::tryFrom($value),
            $this->funding,
        )));
    }

    /** @param Funding[] $funding */
    public function setFunding(array $funding): static
    {
        $this->funding = array_values(array_map(
            static fn (Funding $item): string => $item->value,
            $funding,
        ));

        return $this;
    }

    /** @return Collection<int, Term> */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Term $term): static
    {
        if (!$this->tags->contains($term)) {
            $this->tags->add($term);
        }

        return $this;
    }

    public function removeTag(Term $term): static
    {
        $this->tags->removeElement($term);

        return $this;
    }

    /** @param iterable<Term> $terms */
    public function setTags(iterable $terms): static
    {
        $this->tags->clear();
        foreach ($terms as $term) {
            $this->addTag($term);
        }

        return $this;
    }

    public function getTimePeriodStart(): ?\DateTimeImmutable
    {
        return $this->timePeriodStart;
    }

    public function setTimePeriodStart(?\DateTimeImmutable $timePeriodStart): static
    {
        $this->timePeriodStart = $timePeriodStart;

        return $this;
    }

    public function getTimePeriodEnd(): ?\DateTimeImmutable
    {
        return $this->timePeriodEnd;
    }

    public function setTimePeriodEnd(?\DateTimeImmutable $timePeriodEnd): static
    {
        $this->timePeriodEnd = $timePeriodEnd;

        return $this;
    }

    /** @return list<string> */
    public function getLinks(): array
    {
        return $this->links;
    }

    /** @param list<string> $links */
    public function setLinks(array $links): static
    {
        $this->links = array_values(array_filter(
            array_map(static fn (?string $link): string => trim((string) $link), $links),
            // Keep only non-empty http(s) URLs; drop schemes like javascript: that enable stored XSS.
            static function (string $link): bool {
                if ('' === $link) {
                    return false;
                }
                $scheme = strtolower((string) parse_url($link, \PHP_URL_SCHEME));

                return 'http' === $scheme || 'https' === $scheme;
            },
        ));

        return $this;
    }

    /**
     * Share of {@see COMPLETION_FIELDS} that are filled in, as a 0–100 percentage.
     * Reads only own columns, so it is safe to call per row in a listing.
     */
    public function getCompletionPercentage(): int
    {
        $checks = [
            null !== $this->title && '' !== $this->title,
            null !== $this->area,
            null !== $this->description && '' !== $this->description,
            null !== $this->initiativeType,
            null !== $this->status,
            null !== $this->organizationalAnchoring,
            null !== $this->budget,
            [] !== $this->funding,
            null !== $this->timePeriodStart,
            null !== $this->timePeriodEnd,
        ];

        return (int) round(\count(array_filter($checks)) / \count($checks) * 100);
    }

    /**
     * Whether the initiative has been edited since it was created — drives the
     * "updated" vs "created" label in the dashboard activity feed. Only counts as
     * an edit once it's more than a day past creation, so the initial save and any
     * same-day tweaks still read as "created".
     */
    public function wasUpdatedAfterCreation(): bool
    {
        $created = $this->getCreatedAt();
        $updated = $this->getUpdatedAt();

        if (null === $created || null === $updated) {
            return false;
        }

        return $updated->getTimestamp() - $created->getTimestamp() > 60 * 60 * 24;
    }

    public function __toString(): string
    {
        return (string) $this->title;
    }
}
