<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Twig\MascotExtension;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MascotExtensionTest extends KernelTestCase
{
    public function testContextIsEmptyWhenNoUserIsAuthenticated(): void
    {
        self::bootKernel();
        $extension = static::getContainer()->get(MascotExtension::class);
        \assert($extension instanceof MascotExtension);

        // No user is logged in, so the mascot has no personal numbers to show.
        self::assertSame(['count' => 0, 'unfinished' => null, 'incompleteContact' => null], $extension->context());
    }

    public function testRegistersTheMascotContextFunction(): void
    {
        self::bootKernel();
        $extension = static::getContainer()->get(MascotExtension::class);
        \assert($extension instanceof MascotExtension);

        $names = array_map(static fn (\Twig\TwigFunction $fn): string => $fn->getName(), $extension->getFunctions());
        self::assertContains('mascot_context', $names);
    }
}
