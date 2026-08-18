<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\DataTransformer;

use App\Entity\Partner;
use App\Form\DataTransformer\PartnersTextTransformer;
use App\Repository\PartnerRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

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

    public function testReverseTransformRejectsANameTooLongForTheColumn(): void
    {
        try {
            $this->transformer()->reverseTransform(str_repeat('a', Partner::NAME_MAX_LENGTH + 1));
            self::fail('An over-long partner name should not reach the database.');
        } catch (TransformationFailedException $failure) {
            // Reported on the partners field itself, which a cascaded entity
            // violation could not be.
            self::assertSame('partner.name_too_long', $failure->getInvalidMessage());
        }
    }

    private function transformer(): PartnersTextTransformer
    {
        return new PartnersTextTransformer($this->createStub(PartnerRepository::class));
    }
}
