<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\FunctionalTestCase;

final class UserSettingsControllerTest extends FunctionalTestCase
{
    protected function tearDown(): void
    {
        // Keep the persisted preference from leaking between tests.
        $user = $this->users()->findOneBy(['email' => 'editor@example.com']);
        if ($user instanceof User) {
            $user->setUserSettings([]);
            $this->entityManager()->flush();
        }

        parent::tearDown();
    }

    public function testAjaxToggleReturnsNoContentAndFlipsThePreference(): void
    {
        $this->loginAsEditor();
        $token = $this->mascotToggleToken();

        $this->client->request('POST', '/settings/mascot/toggle', ['_token' => $token], [], ['HTTP_X-Requested-With' => 'fetch']);
        $this->assertResponseStatusCodeSame(204);
        self::assertFalse($this->reloadEditor()->isMascotEnabled());

        $this->client->request('POST', '/settings/mascot/toggle', ['_token' => $token], [], ['HTTP_X-Requested-With' => 'fetch']);
        $this->assertResponseStatusCodeSame(204);
        self::assertTrue($this->reloadEditor()->isMascotEnabled());
    }

    public function testPlainFormToggleRedirectsToReturn(): void
    {
        $this->loginAsEditor();
        $token = $this->mascotToggleToken();

        $this->client->request('POST', '/settings/mascot/toggle', ['_token' => $token, 'return' => '/initiatives']);

        $this->assertResponseRedirects('/initiatives');
        self::assertFalse($this->reloadEditor()->isMascotEnabled());
    }

    public function testInvalidTokenIsIgnoredAndRedirectsToDashboard(): void
    {
        $this->loginAsEditor();

        $this->client->request('POST', '/settings/mascot/toggle', ['_token' => 'not-a-valid-token']);

        $this->assertResponseRedirects('/');
        self::assertTrue($this->reloadEditor()->isMascotEnabled());
    }

    private function mascotToggleToken(): string
    {
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        return (string) $crawler->filter('#mascotToggleForm input[name="_token"]')->attr('value');
    }

    private function reloadEditor(): User
    {
        $this->entityManager()->clear();
        $user = $this->users()->findOneBy(['email' => 'editor@example.com']);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }
}
