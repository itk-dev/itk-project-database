<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\DataTransformer;

use App\Entity\Partner;
use App\Form\DataTransformer\PartnersTextTransformer;
use App\Repository\PartnerRepository;
use PHPUnit\Framework\TestCase;

final class PartnersTextTransformerTest extends TestCase
{
    public function testTransformOfNonIterableReturnsEmptyString(): void
    {
        self::assertSame('', $this->transformer()->transform(null));
    }

    public function testTransformJoinsPartnerNames(): void
    {
        $partners = [
            (new Partner())->setName('Aarhus Universitet'),
            (new Partner())->setName('Alexandra Instituttet'),
        ];

        self::assertSame('Aarhus Universitet, Alexandra Instituttet', $this->transformer()->transform($partners));
    }

    public function testReverseTransformOfNonStringReturnsEmptyCollection(): void
    {
        self::assertCount(0, $this->transformer()->reverseTransform(null));
    }

    public function testReverseTransformOfBlankReturnsEmptyCollection(): void
    {
        self::assertCount(0, $this->transformer()->reverseTransform('   '));
    }

    public function testReverseTransformTrimsDeduplicatesAndResolvesPartners(): void
    {
        $repository = $this->createMock(PartnerRepository::class);
        $repository->expects(self::exactly(2))
            ->method('findOrCreate')
            ->willReturnCallback(static fn (string $name): Partner => (new Partner())->setName($name));

        $transformer = new PartnersTextTransformer($repository);

        // "aarhus universitet" duplicates "Aarhus Universitet" (case-insensitive) and the empty segment is skipped.
        self::assertCount(2, $transformer->reverseTransform('Aarhus Universitet, Alexandra Instituttet, , aarhus universitet'));
    }

    private function transformer(): PartnersTextTransformer
    {
        return new PartnersTextTransformer($this->createStub(PartnerRepository::class));
    }
}
