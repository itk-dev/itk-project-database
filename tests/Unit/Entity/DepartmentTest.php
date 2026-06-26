<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Department;
use PHPUnit\Framework\TestCase;

final class DepartmentTest extends TestCase
{
    public function testDefaults(): void
    {
        $department = new Department();

        self::assertNull($department->getName());
        self::assertSame('', (string) $department);
    }

    public function testAccessors(): void
    {
        $department = (new Department())->setName('Teknik og Miljø');

        self::assertSame('Teknik og Miljø', $department->getName());
        self::assertSame('Teknik og Miljø', (string) $department);
    }
}
