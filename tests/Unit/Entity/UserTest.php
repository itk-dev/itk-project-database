<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testDefaults(): void
    {
        $user = new User();

        self::assertNull($user->getEmail());
        self::assertNull($user->getPassword());
        self::assertSame('', $user->getUserIdentifier());
        self::assertSame([], array_diff($user->getRoles(), ['ROLE_USER']));
    }

    public function testAccessors(): void
    {
        $user = (new User())
            ->setEmail('admin@example.com')
            ->setName('Administrator')
            ->setPassword('hashed');

        self::assertSame('admin@example.com', $user->getEmail());
        self::assertSame('Administrator', $user->getName());
        self::assertSame('admin@example.com', $user->getUserIdentifier());
        self::assertSame('hashed', $user->getPassword());
    }

    public function testNameFallsBackToEmailWhenEmpty(): void
    {
        $user = (new User())->setEmail('editor@example.com');

        self::assertSame('editor@example.com', $user->getName());
    }

    public function testGetRolesAlwaysContainsRoleUserAndIsDeduplicated(): void
    {
        $user = (new User())->setRoles(['ROLE_ADMIN', 'ROLE_USER']);

        $roles = $user->getRoles();

        self::assertContains('ROLE_USER', $roles);
        self::assertContains('ROLE_ADMIN', $roles);
        self::assertSame(array_values(array_unique($roles)), $roles);
        self::assertCount(2, $roles);
    }

    public function testUserSettingsDefaultEmptyAndMascotEnabled(): void
    {
        $user = new User();

        self::assertSame([], $user->getUserSettings());
        // No stored preference means the mascot is shown.
        self::assertTrue($user->isMascotEnabled());
    }

    public function testMascotPreferenceTogglesThroughSettings(): void
    {
        $user = new User();

        $user->setMascotEnabled(false);
        self::assertFalse($user->isMascotEnabled());
        self::assertSame(['mascotEnabled' => false], $user->getUserSettings());

        $user->setMascotEnabled(true);
        self::assertTrue($user->isMascotEnabled());

        // Unrelated settings are preserved and don't affect the mascot default.
        $user->setUserSettings(['theme' => 'dark']);
        self::assertSame(['theme' => 'dark'], $user->getUserSettings());
        self::assertTrue($user->isMascotEnabled());
    }

    public function testEraseCredentialsDoesNothing(): void
    {
        $user = new User();
        $user->eraseCredentials();

        $this->expectNotToPerformAssertions();
    }
}
