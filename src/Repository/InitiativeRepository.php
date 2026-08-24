<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Initiative;
use App\Entity\User;
use App\Enum\EndorsementAuthor;
use App\Enum\Funding;
use App\Enum\InitiativeType;
use App\Enum\Status;
use App\Enum\TranslatableEnum;
use App\Model\InitiativeFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends ServiceEntityRepository<Initiative>
 */
class InitiativeRepository extends ServiceEntityRepository
{
    public const SORTABLE = ['title', 'budget', 'createdAt', 'timePeriodStart'];

    public function __construct(ManagerRegistry $registry, private readonly TranslatorInterface $translator)
    {
        parent::__construct($registry, Initiative::class);
    }

    public function search(InitiativeFilter $filter): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i');

        if (null !== $filter->q && '' !== trim($filter->q)) {
            // Lower-cased for case-insensitive matching. The raw term drives the
            // enum-label lookups below; the LIKE parameter is wildcard-escaped so a
            // user-typed % or _ matches literally (backslash is MariaDB's escape).
            $term = mb_strtolower(trim($filter->q));
            $qb->setParameter('q', '%'.addcslashes($term, '%_\\').'%');

            $ors = [
                'LOWER(i.title) LIKE :q',
                'LOWER(i.topic) LIKE :q',
                'LOWER(i.description) LIKE :q',
                'LOWER(i.statusAdditional) LIKE :q',
                // Related names, matched without joining the root query so the
                // paginator's count stays correct.
                sprintf('i.id IN (SELECT icrt.id FROM %s icrt JOIN icrt.createdBy cb WHERE LOWER(cb.name) LIKE :q)', Initiative::class),
                sprintf('i.id IN (SELECT itag.id FROM %s itag JOIN itag.tags tg WHERE LOWER(tg.name) LIKE :q)', Initiative::class),
                sprintf('i.id IN (SELECT istr.id FROM %s istr JOIN istr.strategies st WHERE LOWER(st.name) LIKE :q)', Initiative::class),
                sprintf('i.id IN (SELECT isth.id FROM %s isth JOIN isth.stakeholders sh WHERE LOWER(sh.name) LIKE :q)', Initiative::class),
                sprintf('i.id IN (SELECT icon.id FROM %s icon JOIN icon.contacts co WHERE LOWER(co.name) LIKE :q)', Initiative::class),
                sprintf('i.id IN (SELECT ipar.id FROM %s ipar JOIN ipar.partners pa WHERE LOWER(pa.name) LIKE :q)', Initiative::class),
                // Department and area are related entities searched by their stored
                // name ("nik" should find "Teknik og Miljø").
                sprintf('i.id IN (SELECT idep.id FROM %s idep JOIN idep.organizationalAnchoring dep WHERE LOWER(dep.name) LIKE :q)', Initiative::class),
                sprintf('i.id IN (SELECT iare.id FROM %s iare JOIN iare.area ar WHERE LOWER(ar.name) LIKE :q)', Initiative::class),
            ];

            // Enum columns store slugs, but the user searches their translated
            // labels ("nik" should find "Teknik og Miljø"); map labels to values.
            $enumFields = [
                'status' => Status::cases(),
                'initiativeType' => InitiativeType::cases(),
                'endorsementAuthor' => EndorsementAuthor::cases(),
            ];
            foreach ($enumFields as $field => $cases) {
                $values = $this->matchEnumLabels($cases, $term);
                if ([] !== $values) {
                    $ors[] = sprintf('i.%s IN (:q_%s)', $field, $field);
                    $qb->setParameter('q_'.$field, $values);
                }
            }

            // Funding is a JSON list of slugs; match the label, then look for the
            // slug inside the stored JSON array.
            foreach ($this->matchEnumLabels(Funding::cases(), $term) as $i => $value) {
                $ors[] = sprintf('i.funding LIKE :q_funding%d', $i);
                $qb->setParameter('q_funding'.$i, '%"'.$value.'"%');
            }

            $qb->andWhere('('.implode(' OR ', $ors).')');
        }

        if (null !== $filter->status) {
            $qb->andWhere('i.status = :status')->setParameter('status', $filter->status->value);
        }

        if (null !== $filter->area) {
            $qb->andWhere('i.area = :area')->setParameter('area', $filter->area);
        }

        if (null !== $filter->initiativeType) {
            $qb->andWhere('i.initiativeType = :initiativeType')->setParameter('initiativeType', $filter->initiativeType->value);
        }

        if (null !== $filter->organizationalAnchoring) {
            $qb->andWhere('i.organizationalAnchoring = :anchoring')->setParameter('anchoring', $filter->organizationalAnchoring);
        }

        if (null !== $filter->endorsement) {
            $qb->andWhere('i.endorsement = :endorsement')->setParameter('endorsement', $filter->endorsement);
        }

        $sort = \in_array($filter->sort, self::SORTABLE, true) ? $filter->sort : 'createdAt';
        $direction = 'ASC' === strtoupper($filter->direction) ? 'ASC' : 'DESC';

        return $qb->orderBy('i.'.$sort, $direction);
    }

    /**
     * Backing values of the enum cases whose translated label contains the term.
     *
     * @param array<\BackedEnum&TranslatableEnum> $cases
     *
     * @return list<string>
     */
    private function matchEnumLabels(array $cases, string $lowerTerm): array
    {
        $values = [];
        foreach ($cases as $case) {
            if (str_contains(mb_strtolower($this->translator->trans($case->labelKey())), $lowerTerm)) {
                $values[] = (string) $case->value;
            }
        }

        return $values;
    }

    /**
     * Returns the filtered initiatives with every to-many collection primed, so
     * a CSV export can read them without firing a query per row (N+1). Each
     * association is loaded in its own query; fetch-joining them all at once
     * would multiply rows (a cartesian product) instead of cutting queries.
     *
     * @return Initiative[]
     */
    public function findForExport(InitiativeFilter $filter): array
    {
        /** @var Initiative[] $initiatives */
        $initiatives = $this->search($filter)->getQuery()->getResult();

        if ([] === $initiatives) {
            return [];
        }

        foreach (['strategies', 'stakeholders', 'tags', 'contacts', 'partners'] as $association) {
            $this->createQueryBuilder('i')
                ->addSelect('rel')
                ->leftJoin('i.'.$association, 'rel')
                ->andWhere('i IN (:initiatives)')
                ->setParameter('initiatives', $initiatives)
                ->getQuery()
                ->getResult();
        }

        return $initiatives;
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByCreator(User $user): int
    {
        // createdBy is a ManyToOne to the UserInterface (resolved to User via
        // resolve_target_entities); binding the entity to a ULID FK doesn't match,
        // so compare the raw FK against the user's id with the ulid type applied.
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('IDENTITY(i.createdBy) = :user')
            ->setParameter('user', $user->getId(), 'ulid')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * The creator's least-complete initiative that isn't fully filled in yet, or
     * null if none are outstanding.
     */
    public function findUnfinishedByCreator(User $user): ?Initiative
    {
        return $this->findUnfinishedListByCreator($user, 1)[0] ?? null;
    }

    /**
     * The creator's incomplete initiatives (completion below 100 %), least-complete
     * first, capped at $limit. Completion is computed in PHP (not a stored column),
     * so this scans only the creator's 50 most recent initiatives.
     *
     * @return Initiative[]
     */
    public function findUnfinishedListByCreator(User $user, int $limit = 6): array
    {
        // See countByCreator: match the raw ULID FK, not the entity.
        $initiatives = $this->createQueryBuilder('i')
            ->andWhere('IDENTITY(i.createdBy) = :user')
            ->setParameter('user', $user->getId(), 'ulid')
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        $unfinished = array_filter(
            $initiatives,
            static fn (Initiative $initiative): bool => $initiative->getCompletionPercentage() < 100,
        );

        usort(
            $unfinished,
            static fn (Initiative $a, Initiative $b): int => $a->getCompletionPercentage() <=> $b->getCompletionPercentage(),
        );

        return \array_slice($unfinished, 0, $limit);
    }

    /**
     * @return array<string, int> count keyed by status value (skips initiatives without a status)
     */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('i')
            ->select('i.status AS status, COUNT(i.id) AS cnt')
            ->groupBy('i.status')
            ->getQuery()
            ->getScalarResult();

        $counts = [];
        foreach ($rows as $row) {
            // getScalarResult() returns the raw column value, so $row['status']
            // is the enum's backing string (or null), never a Status instance.
            if (null === $row['status']) {
                continue;
            }
            $counts[(string) $row['status']] = (int) $row['cnt'];
        }

        return $counts;
    }

    /**
     * Most recently touched initiatives (created or edited) for the activity feed,
     * newest first. Ordered by updatedAt so an edit resurfaces the initiative.
     *
     * @return Initiative[]
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Lightweight per-initiative rows for the dashboard visualisations: just the
     * columns the aggregates need, no relations, so it stays cheap to recompute
     * on every live broadcast.
     *
     * @return list<array<string, mixed>>
     */
    public function dashboardRows(): array
    {
        // Join and select the related ids (rather than IDENTITY()) so Doctrine
        // applies the ULID type: IDENTITY() returns the raw binary FK, which would
        // not match the canonical ULID strings the rest of build() keys on.
        return $this->createQueryBuilder('i')
            ->select(
                'i.title',
                'ar.id AS area',
                'i.status',
                'department.id AS organizationalAnchoring',
                'i.budget',
                'i.funding',
                'i.timePeriodStart',
                'i.timePeriodEnd',
            )
            ->leftJoin('i.area', 'ar')
            ->leftJoin('i.organizationalAnchoring', 'department')
            ->getQuery()
            ->getArrayResult();
    }
}
