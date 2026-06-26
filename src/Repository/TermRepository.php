<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Term;
use App\Enum\Vocabulary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Term>
 */
class TermRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Term::class);
    }

    /**
     * @return Term[]
     */
    public function findByVocabulary(Vocabulary $vocabulary): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.vocabulary = :vocabulary')
            ->setParameter('vocabulary', $vocabulary->value)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Return an existing term (case-insensitive) or a new, unflushed one.
     * Supports the free-tagging vocabularies where terms are created on the fly.
     */
    public function findOrCreate(string $name, Vocabulary $vocabulary): Term
    {
        $name = trim($name);

        $existing = $this->createQueryBuilder('t')
            ->andWhere('LOWER(t.name) = :name')
            ->andWhere('t.vocabulary = :vocabulary')
            ->setParameter('name', mb_strtolower($name))
            ->setParameter('vocabulary', $vocabulary->value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($existing instanceof Term) {
            return $existing;
        }

        $term = (new Term($vocabulary))->setName($name);
        $this->getEntityManager()->persist($term);

        return $term;
    }
}
