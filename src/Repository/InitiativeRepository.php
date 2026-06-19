<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Initiative;
use App\Enum\Status;
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
            $qb->andWhere('LOWER(i.title) LIKE :q OR LOWER(i.description) LIKE :q OR LOWER(i.author) LIKE :q OR LOWER(i.statusAdditional) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower(trim($filter->q)).'%');
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

        if (null !== $filter->published) {
            $qb->andWhere('i.published = :published')->setParameter('published', $filter->published);
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

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPublished(bool $published = true): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.published = :published')
            ->setParameter('published', $published)
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
            $status = $row['status'];
            if (null === $status) {
                continue;
            }
            $key = $status instanceof Status ? $status->value : (string) $status;
            $counts[$key] = (int) $row['cnt'];
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
}
