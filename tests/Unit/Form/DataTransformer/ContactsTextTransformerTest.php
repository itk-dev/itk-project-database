<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\DataTransformer;

use App\Entity\Contact;
use App\Form\DataTransformer\ContactsTextTransformer;
use App\Repository\ContactRepository;
use PHPUnit\Framework\TestCase;

final class ContactsTextTransformerTest extends TestCase
{
    public function testTransformOfNonIterableReturnsEmptyString(): void
    {
        self::assertSame('', $this->transformer()->transform(null));
    }

    public function testTransformJoinsContactNames(): void
    {
        $contacts = [
            (new Contact())->setName('Anne Jensen'),
            (new Contact())->setName('Lars Holm'),
        ];

        self::assertSame('Anne Jensen, Lars Holm', $this->transformer()->transform($contacts));
    }

    public function testReverseTransformOfNonStringReturnsEmptyCollection(): void
    {
        self::assertCount(0, $this->transformer()->reverseTransform(null));
    }

    public function testReverseTransformOfBlankReturnsEmptyCollection(): void
    {
        self::assertCount(0, $this->transformer()->reverseTransform('   '));
    }

    public function testReverseTransformTrimsDeduplicatesAndResolvesContacts(): void
    {
        $repository = $this->createMock(ContactRepository::class);
        $repository->expects(self::exactly(2))
            ->method('findOrCreate')
            ->willReturnCallback(static fn (string $name): Contact => (new Contact())->setName($name));

        $transformer = new ContactsTextTransformer($repository);

        // "anne jensen" duplicates "Anne Jensen" (case-insensitive) and the empty segment is skipped.
        self::assertCount(2, $transformer->reverseTransform('Anne Jensen, Lars Holm, , anne jensen'));
    }

    private function transformer(): ContactsTextTransformer
    {
        return new ContactsTextTransformer($this->createStub(ContactRepository::class));
    }
}
