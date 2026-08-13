<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Initiative;
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
        $em = static::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        // A shared prefix keeps the two apart from whatever else the fixtures hold,
        // and they are persisted in reverse so the ordering cannot come from
        // insertion order.
        $prefix = 'Ordered '.uniqid().' ';
        $second = (new Partner())->setName($prefix.'B');
        $first = (new Partner())->setName($prefix.'A');
        $em->persist($second);
        $em->persist($first);
        $em->flush();

        $names = array_values(array_filter(
            array_map(static fn (Partner $partner): string => (string) $partner->getName(), $repository->findAllOrdered()),
            static fn (string $name): bool => str_starts_with($name, $prefix),
        ));
        self::assertSame([$prefix.'A', $prefix.'B'], $names);

        $em->remove($first);
        $em->remove($second);
        $em->flush();
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

    public function testFindInitiativeUsageNamesTheReferencingInitiatives(): void
    {
        self::bootKernel();
        $repository = static::getContainer()->get(PartnerRepository::class);
        \assert($repository instanceof PartnerRepository);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        $partner = (new Partner())->setName('Usage Partner '.uniqid());
        $initiative = (new Initiative())->setTitle('Usage Initiative '.uniqid());
        $initiative->addPartner($partner);
        $em->persist($partner);
        $em->persist($initiative);
        $em->flush();

        $expected = [['id' => (string) $initiative->getId(), 'title' => $initiative->getTitle()]];
        self::assertSame($expected, $repository->findInitiativesUsing($partner));

        // The bulk variant backing the admin list must agree with the single lookup.
        $usage = $repository->findInitiativeUsage();
        self::assertSame($expected, $usage[(string) $partner->getId()] ?? []);

        $em->remove($initiative);
        $em->remove($partner);
        $em->flush();
    }

    public function testFindInitiativeUsageOmitsUnusedPartners(): void
    {
        self::bootKernel();
        $repository = static::getContainer()->get(PartnerRepository::class);
        \assert($repository instanceof PartnerRepository);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        $partner = (new Partner())->setName('Unused Partner '.uniqid());
        $em->persist($partner);
        $em->flush();

        self::assertSame([], $repository->findInitiativesUsing($partner));
        self::assertArrayNotHasKey((string) $partner->getId(), $repository->findInitiativeUsage());

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
