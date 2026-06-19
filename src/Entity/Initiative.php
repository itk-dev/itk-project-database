<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\Category;
use App\Enum\EndorsementAuthor;
use App\Enum\Funding;
use App\Enum\InitiativeType;
use App\Enum\OrganizationalAnchoring;
use App\Enum\Status;
use App\Repository\InitiativeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InitiativeRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    shortName: 'Initiative',
    operations: [new GetCollection(), new Get()],
    normalizationContext: ['groups' => ['initiative:read']],
    paginationItemsPerPage: 50,
    paginationClientItemsPerPage: true,
    order: ['createdAt' => 'DESC'],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'author' => 'partial',
    'status' => 'exact',
    'category' => 'exact',
    'initiativeType' => 'exact',
    'organizationalAnchoring' => 'exact',
    'endorsementAuthor' => 'exact',
    'tags.name' => 'exact',
    'stakeholders.name' => 'exact',
    'contacts.name' => 'partial',
])]
#[ApiFilter(BooleanFilter::class, properties: ['endorsement', 'published'])]
#[ApiFilter(RangeFilter::class, properties: ['budget'])]
#[ApiFilter(DateFilter::class, properties: ['timePeriodStart', 'timePeriodEnd', 'createdAt'])]
#[ApiFilter(OrderFilter::class, properties: ['title', 'budget', 'createdAt', 'timePeriodStart'])]
class Initiative
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['initiative:read'])]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(length: 255)]
    #[Groups(['initiative:read'])]
    private ?string $title = null;

    #[ORM\Column(length: 32, nullable: true, enumType: Category::class)]
    #[Groups(['initiative:read'])]
    private ?Category $category = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['initiative:read'])]
    private ?string $description = null;

    /** @var Collection<int, Term> */
    #[ORM\ManyToMany(targetEntity: Term::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'initiative_strategy')]
    #[Groups(['initiative:read'])]
    private Collection $strategies;

    #[ORM\Column(length: 32, nullable: true, enumType: InitiativeType::class)]
    #[Groups(['initiative:read'])]
    private ?InitiativeType $initiativeType = null;

    #[ORM\Column(length: 32, nullable: true, enumType: Status::class)]
    #[Groups(['initiative:read'])]
    private ?Status $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['initiative:read'])]
    private ?string $statusAdditional = null;

    #[ORM\Column(length: 64, nullable: true, enumType: OrganizationalAnchoring::class)]
    #[Groups(['initiative:read'])]
    private ?OrganizationalAnchoring $organizationalAnchoring = null;

    #[ORM\Column]
    #[Groups(['initiative:read'])]
    private bool $endorsement = true;

    #[ORM\Column(length: 32, nullable: true, enumType: EndorsementAuthor::class)]
    #[Groups(['initiative:read'])]
    private ?EndorsementAuthor $endorsementAuthor = null;

    /** @var Collection<int, Contact> */
    #[ORM\ManyToMany(targetEntity: Contact::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'initiative_contact')]
    private Collection $contacts;

    /** @var Collection<int, InitiativeImage> */
    #[ORM\OneToMany(targetEntity: InitiativeImage::class, mappedBy: 'initiative', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $images;

    /** @var Collection<int, InitiativeAttachment> */
    #[ORM\OneToMany(targetEntity: InitiativeAttachment::class, mappedBy: 'initiative', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $attachments;

    /** @var Collection<int, Term> */
    #[ORM\ManyToMany(targetEntity: Term::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'initiative_stakeholder')]
    #[Groups(['initiative:read'])]
    private Collection $stakeholders;

    #[Assert\PositiveOrZero]
    #[ORM\Column(nullable: true)]
    #[Groups(['initiative:read'])]
    private ?int $budget = null;

    /**
     * Stored as the backing values of {@see Funding}; accessors expose enums.
     *
     * @var list<string>
     */
    #[ORM\Column]
    #[Groups(['initiative:read'])]
    private array $funding = [];

    /** @var Collection<int, Term> */
    #[ORM\ManyToMany(targetEntity: Term::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'initiative_tag')]
    #[Groups(['initiative:read'])]
    private Collection $tags;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['initiative:read'])]
    private ?\DateTimeImmutable $timePeriodStart = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['initiative:read'])]
    private ?\DateTimeImmutable $timePeriodEnd = null;

    /** @var list<string> */
    #[ORM\Column]
    #[Groups(['initiative:read'])]
    private array $links = [];

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['initiative:read'])]
    private ?string $author = null;

    #[ORM\Column]
    #[Groups(['initiative:read'])]
    private bool $published = true;

    #[ORM\Column]
    #[Groups(['initiative:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    #[Groups(['initiative:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->strategies = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->stakeholders = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

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

    public function getOrganizationalAnchoring(): ?OrganizationalAnchoring
    {
        return $this->organizationalAnchoring;
    }

    public function setOrganizationalAnchoring(?OrganizationalAnchoring $organizationalAnchoring): static
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
        $this->links = array_values(array_filter($links, static fn (?string $link): bool => null !== $link && '' !== trim($link)));

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): static
    {
        $this->published = $published;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function __toString(): string
    {
        return (string) $this->title;
    }
}
