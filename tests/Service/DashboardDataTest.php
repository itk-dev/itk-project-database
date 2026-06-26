<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\DashboardData;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DashboardDataTest extends KernelTestCase
{
    public function testBuildAggregatesDepartmentData(): void
    {
        self::bootKernel();
        $dashboard = static::getContainer()->get(DashboardData::class);
        \assert($dashboard instanceof DashboardData);

        $data = $dashboard->build();

        self::assertGreaterThan(0, $data['kpis']['total']);
        self::assertGreaterThan(0, $data['kpis']['departmentsTotal']);
        // Fixtures set organizationalAnchoring on every initiative, so the
        // department-keyed aggregates must be populated.
        self::assertGreaterThan(0, $data['kpis']['departments'], 'deptsSeen should be > 0');
        self::assertGreaterThan(0, array_sum(array_map('array_sum', $data['heatmap'])), 'heatmap should have entries');
    }
}
