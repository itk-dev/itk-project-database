<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\FunctionalTestCase;

final class LocaleControllerTest extends FunctionalTestCase
{
    public function testStoresLocaleAndRedirectsToDashboardByDefault(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/locale/en');

        $this->assertResponseRedirects('/');
    }

    public function testFollowsSafeRelativeReturnPath(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/locale/da?return=/contacts');

        $this->assertResponseRedirects('/contacts');
    }

    public function testRejectsProtocolRelativeReturnPath(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/locale/da?return=//evil.example');

        $this->assertResponseRedirects('/');
    }

    public function testRejectsBackslashReturnPath(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/locale/en?return=/\\evil.example');

        $this->assertResponseRedirects('/');
    }
}
