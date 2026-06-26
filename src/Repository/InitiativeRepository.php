<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Initiative;
use App\Model\InitiativeFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Initiative>
 */
class InitiativeRepository extends ServiceEntityRepository
{
    public const SORTABLE = ['title', 'budget', 'createdAt', 'timePeriodStart'];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Initiative::class);
    }

    public function search(InitiativeFilter $filter): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i');

        if (null !== $filter->q && '' !== trim($filter->q)) {
            // Escape LIKE wildcards so a user-typed % or _ is matched literally
            // instead of acting as a wildcard. Backslash is MariaDB's default
            // LIKE escape character.
            $term = addcslashes(mb_strtolower(trim($filter->q)), '%_\\');
            $qb->leftJoin('i.createdBy', 'createdBy')
                ->andWhere('LOWER(i.title) LIKE :q OR LOWER(i.description) LIKE :q OR LOWER(createdBy.name) LIKE :q OR LOWER(i.statusAdditional) LIKE :q')
                ->setParameter('q', '%'.$term.'%');
        }

        if (null !== $filter->status) {
            $qb->andWhere('i.status = :status')->setParameter('status', $filter->status->value);
        }

        if (null !== $filter->category) {
            $qb->andWhere('i.category = :category')->setParameter('category', $filter->category->value);
        }

        if (null !== $filter->initiativeType) {
            $qb->andWhere('i.initiativeType = :initiativeType')->setParameter('initiativeType', $filter->initiativeType->value);
        }

        if (null !== $filter->organizationalAnchoring) {
            $qb->andWhere('i.organizationalAnchoring = :anchoring')->setParameter('anchoring', $filter->organizationalAnchoring->value);
        }

        if (null !== $filter->endorsement) {
            $qb->andWhere('i.endorsement = :endorsement')->setParameter('endorsement', $filter->endorsement);
        }

        if (null !== $filter->budgetMin) {
            $qb->andWhere('i.budget >= :budgetMin')->setParameter('budgetMin', $filter->budgetMin);
        }

        if (null !== $filter->budgetMax) {
            $qb->andWhere('i.budget <= :budgetMax')->setParameter('budgetMax', $filter->budgetMax);
        }

        $sort = \in_array($filter->sort, self::SORTABLE, true) ? $filter->sort : 'createdAt';
        $direction = 'ASC' === strtoupper($filter->direction) ? 'ASC' : 'DESC';

        return $qb->orderBy('i.'.$sort, $direction);
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

        foreach (['strategies', 'stakeholders', 'tags', 'contacts'] as $association) {
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
     * @return Initiative[]
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function dashboardRows(): array
    {
        return $this->createQueryBuilder('i')
            ->select(
                'i.title',
                'i.category',
                'i.status',
                'i.organizationalAnchoring',
                'i.budget',
                'i.funding',
                'i.timePeriodStart',
                'i.timePeriodEnd',
            )
            ->getQuery()
            ->getArrayResult();
    }
}
