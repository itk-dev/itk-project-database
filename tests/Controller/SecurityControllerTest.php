<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\FunctionalTestCase;

final class SecurityControllerTest extends FunctionalTestCase
{
    public function testLoginPageRendersForAnonymousUsers(): void
    {
        $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="email"], input[name="_username"], form');
    }

    public function testLoginRedirectsAlreadyAuthenticatedUsers(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/login');

        $this->assertResponseRedirects('/');
    }

    public function testLogoutIsHandledByTheFirewall(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/logout');

        // The firewall intercepts /logout and redirects to the login target.
        $this->assertResponseRedirects();
    }
}
