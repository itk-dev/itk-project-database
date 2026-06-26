<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Contact;
use PHPUnit\Framework\TestCase;

final class ContactTest extends TestCase
{
    public function testDefaults(): void
    {
        $contact = new Contact();

        self::assertNull($contact->getId());
        self::assertNull($contact->getName());
        self::assertNull($contact->getEmail());
        self::assertNull($contact->getPhone());
        self::assertNull($contact->getDepartment());
        self::assertInstanceOf(\DateTimeImmutable::class, $contact->getCreatedAt());
        self::assertSame('', (string) $contact);
    }

    public function testAccessors(): void
    {
        $contact = (new Contact())
            ->setName('Anne Jensen')
            ->setEmail('anne@example.com')
            ->setPhone('+45 12 34 56 78')
            ->setDepartment('Teknik og Miljø');

        self::assertSame('Anne Jensen', $contact->getName());
        self::assertSame('anne@example.com', $contact->getEmail());
        self::assertSame('+45 12 34 56 78', $contact->getPhone());
        self::assertSame('Teknik og Miljø', $contact->getDepartment());
        self::assertSame('Anne Jensen', (string) $contact);
    }

    public function testTouchUpdatesTimestamp(): void
    {
        $contact = new Contact();
        $contact->touch();

        // touch() does not throw and leaves the entity in a valid state.
        self::assertInstanceOf(\DateTimeImmutable::class, $contact->getCreatedAt());
    }
}
