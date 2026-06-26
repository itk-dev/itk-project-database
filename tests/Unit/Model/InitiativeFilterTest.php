<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Enum\Category;
use App\Enum\InitiativeType;
use App\Enum\OrganizationalAnchoring;
use App\Enum\Status;
use App\Model\InitiativeFilter;
use PHPUnit\Framework\TestCase;

final class InitiativeFilterTest extends TestCase
{
    public function testDefaults(): void
    {
        $filter = new InitiativeFilter();

        self::assertNull($filter->q);
        self::assertNull($filter->status);
        self::assertNull($filter->category);
        self::assertNull($filter->initiativeType);
        self::assertNull($filter->organizationalAnchoring);
        self::assertNull($filter->endorsement);
        self::assertNull($filter->budgetMin);
        self::assertNull($filter->budgetMax);
        self::assertSame('createdAt', $filter->sort);
        self::assertSame('DESC', $filter->direction);
    }

    public function testIsMutable(): void
    {
        $filter = new InitiativeFilter();
        $filter->q = 'klima';
        $filter->status = Status::Active;
        $filter->category = Category::Climate;
        $filter->initiativeType = InitiativeType::Project;
        $filter->organizationalAnchoring = OrganizationalAnchoring::HealthAndCare;
        $filter->endorsement = true;
        $filter->budgetMin = 1000;
        $filter->budgetMax = 5000;
        $filter->sort = 'title';
        $filter->direction = 'ASC';

        self::assertSame('klima', $filter->q);
        self::assertSame(Status::Active, $filter->status);
        self::assertTrue($filter->endorsement);
        self::assertSame(1000, $filter->budgetMin);
        self::assertSame(5000, $filter->budgetMax);
    }
}
