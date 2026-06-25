<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Term;
use App\Enum\Vocabulary;
use PHPUnit\Framework\TestCase;

final class TermTest extends TestCase
{
    public function testDefaultVocabularyIsTag(): void
    {
        $term = new Term();

        self::assertNull($term->getName());
        self::assertSame(Vocabulary::Tag, $term->getVocabulary());
        self::assertInstanceOf(\DateTimeImmutable::class, $term->getCreatedAt());
        self::assertSame('', (string) $term);
    }

    public function testAccessors(): void
    {
        $term = (new Term(Vocabulary::Stakeholder))->setName('Aarhus Kommune');

        self::assertSame('Aarhus Kommune', $term->getName());
        self::assertSame(Vocabulary::Stakeholder, $term->getVocabulary());
        self::assertSame('Aarhus Kommune', (string) $term);

        $term->setVocabulary(Vocabulary::Strategy);
        self::assertSame(Vocabulary::Strategy, $term->getVocabulary());
    }
}
