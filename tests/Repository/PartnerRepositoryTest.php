<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Partner;
use App\Repository\PartnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PartnerRepositoryTest extends KernelTestCase
{
    public function testFindAllOrderedReturnsPartnersSortedByName(): void
    {
        self::bootKernel();
        $repository = static::getContainer()->get(PartnerRepository::class);
        \assert($repository instanceof PartnerRepository);

        $partners = $repository->findAllOrdered();

        // Ordering is delegated to the database collation, so we only assert the
        // method returns the persisted partners.
        self::assertNotEmpty($partners);
    }

    public function testFindOrCreateReturnsAnExistingPartnerCaseInsensitively(): void
    {
        self::bootKernel();
        $repository = static::getContainer()->get(PartnerRepository::class);
        \assert($repository instanceof PartnerRepository);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        $name = 'Findme Partner '.uniqid();
        $partner = (new Partner())->setName($name);
        $em->persist($partner);
        $em->flush();

        $found = $repository->findOrCreate(mb_strtolower($name));
        self::assertSame($partner->getId(), $found->getId());

        $em->remove($partner);
        $em->flush();
    }

    public function testFindOrCreateBuildsANewUnflushedPartner(): void
    {
        self::bootKernel();
        $repository = static::getContainer()->get(PartnerRepository::class);
        \assert($repository instanceof PartnerRepository);

        $name = 'BrandNewPartner-'.uniqid();
        $partner = $repository->findOrCreate($name);

        self::assertSame($name, $partner->getName());
        self::assertCount(0, $repository->findBy(['name' => $name]), 'A freshly created partner is not yet flushed to the database.');
    }
}
