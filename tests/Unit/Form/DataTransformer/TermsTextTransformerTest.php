<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\DataTransformer;

use App\Entity\Term;
use App\Enum\Vocabulary;
use App\Form\DataTransformer\TermsTextTransformer;
use App\Repository\TermRepository;
use PHPUnit\Framework\TestCase;

final class TermsTextTransformerTest extends TestCase
{
    public function testTransformOfNonIterableReturnsEmptyString(): void
    {
        self::assertSame('', $this->transformer()->transform(null));
    }

    public function testTransformJoinsTermNames(): void
    {
        $terms = [
            (new Term(Vocabulary::Tag))->setName('Klima'),
            (new Term(Vocabulary::Tag))->setName('Data'),
        ];

        self::assertSame('Klima, Data', $this->transformer()->transform($terms));
    }

    public function testReverseTransformOfNonStringReturnsEmptyCollection(): void
    {
        self::assertCount(0, $this->transformer()->reverseTransform(null));
    }

    public function testReverseTransformOfBlankReturnsEmptyCollection(): void
    {
        self::assertCount(0, $this->transformer()->reverseTransform('   '));
    }

    public function testReverseTransformTrimsDeduplicatesAndCreatesTerms(): void
    {
        $repository = $this->createMock(TermRepository::class);
        $repository->expects(self::exactly(2))
            ->method('findOrCreate')
            ->willReturnCallback(static fn (string $name, Vocabulary $vocabulary): Term => (new Term($vocabulary))->setName($name));

        $transformer = new TermsTextTransformer($repository, Vocabulary::Tag);

        // "klima" duplicates "Klima" (case-insensitive) and the empty segment is skipped.
        self::assertCount(2, $transformer->reverseTransform('Klima, Data, , klima'));
    }

    private function transformer(): TermsTextTransformer
    {
        return new TermsTextTransformer($this->createStub(TermRepository::class), Vocabulary::Tag);
    }
}
