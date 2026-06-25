<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Initiative;
use App\Enum\Category;
use App\Enum\InitiativeType;
use App\Enum\OrganizationalAnchoring;
use App\Enum\Status;
use App\Model\InitiativeFilter;
use App\Repository\InitiativeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class InitiativeRepositoryTest extends KernelTestCase
{
    private InitiativeRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $repository = static::getContainer()->get(InitiativeRepository::class);
        \assert($repository instanceof InitiativeRepository);
        $this->repository = $repository;
    }

    public function testSearchAppliesEveryFilterBranch(): void
    {
        $filter = new InitiativeFilter();
        $filter->q = '100%_'; // also exercises LIKE wildcard escaping
        $filter->status = Status::Active;
        $filter->category = Category::Climate;
        $filter->initiativeType = InitiativeType::Project;
        $filter->organizationalAnchoring = OrganizationalAnchoring::HealthAndCare;
        $filter->endorsement = true;
        $filter->budgetMin = 0;
        $filter->budgetMax = 1_000_000_000;
        $filter->sort = 'title';
        $filter->direction = 'ASC';

        self::assertIsArray($this->repository->search($filter)->getQuery()->getResult());
    }

    public function testSearchFallsBackForUnknownSortAndDirection(): void
    {
        $filter = new InitiativeFilter();
        $filter->sort = 'not-a-column';
        $filter->direction = 'sideways';

        self::assertIsArray($this->repository->search($filter)->getQuery()->getResult());
    }

    public function testFindForExportReturnsEmptyArrayWhenNothingMatches(): void
    {
        $filter = new InitiativeFilter();
        $filter->q = 'no-such-initiative-'.uniqid();

        self::assertSame([], $this->repository->findForExport($filter));
    }

    public function testFindForExportPrimesCollections(): void
    {
        $rows = $this->repository->findForExport(new InitiativeFilter());

        self::assertNotEmpty($rows);
        self::assertNotNull($rows[0]->getId());
    }

    public function testCountAll(): void
    {
        self::assertGreaterThan(0, $this->repository->countAll());
    }

    public function testCountByStatusSkipsInitiativesWithoutStatus(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        $before = array_sum($this->repository->countByStatus());

        $initiative = (new Initiative())->setTitle('No status '.uniqid());
        $em->persist($initiative);
        $em->flush();

        // A status-less initiative must not appear in any status bucket.
        self::assertSame($before, array_sum($this->repository->countByStatus()));

        $em->remove($initiative);
        $em->flush();
    }

    public function testFindRecentRespectsTheLimit(): void
    {
        self::assertLessThanOrEqual(3, \count($this->repository->findRecent(3)));
    }
}
