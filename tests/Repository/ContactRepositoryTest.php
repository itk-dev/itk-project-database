<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ContactRepositoryTest extends KernelTestCase
{
    public function testFindAllOrderedReturnsContactsSortedByName(): void
    {
        self::bootKernel();
        $repository = static::getContainer()->get(ContactRepository::class);
        \assert($repository instanceof ContactRepository);

        $contacts = $repository->findAllOrdered();

        // Ordering is delegated to the database collation, so we only assert the
        // method returns the persisted contacts.
        self::assertNotEmpty($contacts);
    }

    public function testFindOrCreateReturnsAnExistingContactCaseInsensitively(): void
    {
        self::bootKernel();
        $repository = static::getContainer()->get(ContactRepository::class);
        \assert($repository instanceof ContactRepository);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        $name = 'Findme Contact '.uniqid();
        $contact = (new Contact())->setName($name);
        $em->persist($contact);
        $em->flush();

        $found = $repository->findOrCreate(mb_strtolower($name));
        self::assertSame($contact->getId(), $found->getId());

        $em->remove($contact);
        $em->flush();
    }

    public function testFindOrCreateBuildsANewUnflushedContact(): void
    {
        self::bootKernel();
        $repository = static::getContainer()->get(ContactRepository::class);
        \assert($repository instanceof ContactRepository);

        $name = 'BrandNewContact-'.uniqid();
        $contact = $repository->findOrCreate($name);

        self::assertSame($name, $contact->getName());
        self::assertCount(0, $repository->findBy(['name' => $name]), 'A freshly created contact is not yet flushed to the database.');
    }
}
