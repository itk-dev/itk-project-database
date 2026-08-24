<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Partner;
use PHPUnit\Framework\TestCase;

final class PartnerTest extends TestCase
{
    public function testDefaults(): void
    {
        $partner = new Partner();

        self::assertNull($partner->getName());
        self::assertNull($partner->getDescription());
        self::assertNull($partner->getWebsite());
        // Timestamps are populated by the bundle's listener on flush, so they
        // are still null on a freshly constructed (unpersisted) entity.
        self::assertNull($partner->getCreatedAt());
        self::assertSame('', (string) $partner);
    }

    public function testAccessors(): void
    {
        $partner = (new Partner())
            ->setName('Aarhus Universitet')
            ->setDescription('Forsknings- og uddannelsesinstitution.')
            ->setWebsite('https://www.au.dk');

        self::assertSame('Aarhus Universitet', $partner->getName());
        self::assertSame('Forsknings- og uddannelsesinstitution.', $partner->getDescription());
        self::assertSame('https://www.au.dk', $partner->getWebsite());
        self::assertSame('Aarhus Universitet', (string) $partner);
    }
}
