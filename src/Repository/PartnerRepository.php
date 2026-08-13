<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Partner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Partner>
 */
class PartnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Partner::class);
    }

    /**
     * @return Partner[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Return an existing partner matched on name (case-insensitive) or a new,
     * unflushed one. Lets partners be picked from the shared pool or typed in on
     * the fly; the extra fields (description, website) are filled in later under
     * the partners admin.
     */
    public function findOrCreate(string $name): Partner
    {
        $name = trim($name);

        $existing = $this->createQueryBuilder('p')
            ->andWhere('LOWER(p.name) = :name')
            ->setParameter('name', mb_strtolower($name))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($existing instanceof Partner) {
            return $existing;
        }

        $partner = (new Partner())->setName($name);
        $this->getEntityManager()->persist($partner);

        return $partner;
    }
}
