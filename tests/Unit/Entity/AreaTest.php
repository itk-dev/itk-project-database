<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Area;
use PHPUnit\Framework\TestCase;

final class AreaTest extends TestCase
{
    public function testDefaults(): void
    {
        $area = new Area();

        self::assertNull($area->getName());
        self::assertSame('', (string) $area);
    }

    public function testAccessors(): void
    {
        $area = (new Area())->setName('Klima og miljø');

        self::assertSame('Klima og miljø', $area->getName());
        self::assertSame('Klima og miljø', (string) $area);
    }
}
