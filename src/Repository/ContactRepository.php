<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contact>
 */
class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    /**
     * @return Contact[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Return an existing contact matched on name (case-insensitive) or a new,
     * unflushed one. Lets people be picked from the shared pool or typed in on
     * the fly; the extra fields (email, phone, department) are filled in later
     * under the contacts admin.
     */
    public function findOrCreate(string $name): Contact
    {
        $name = trim($name);

        $existing = $this->createQueryBuilder('c')
            ->andWhere('LOWER(c.name) = :name')
            ->setParameter('name', mb_strtolower($name))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($existing instanceof Contact) {
            return $existing;
        }

        $contact = (new Contact())->setName($name);
        $this->getEntityManager()->persist($contact);

        return $contact;
    }
}
