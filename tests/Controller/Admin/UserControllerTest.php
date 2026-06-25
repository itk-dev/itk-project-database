<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use App\Tests\FunctionalTestCase;

final class UserControllerTest extends FunctionalTestCase
{
    public function testIndexIsForbiddenForNonAdmins(): void
    {
        $this->loginAsEditor();
        $this->client->request('GET', '/admin/users');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testNewCreatesUser(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/users/new');
        $this->assertResponseIsSuccessful();

        $email = sprintf('new.user.%s@example.com', uniqid());
        $token = (string) $crawler->filter('input[name="user[_token]"]')->attr('value');
        $this->client->request('POST', '/admin/users/new', [
            'user' => [
                'email' => $email,
                'name' => 'New User',
                'roles' => ['ROLE_USER'],
                'plainPassword' => 'longenoughpassword',
                '_token' => $token,
            ],
        ]);

        $this->assertResponseRedirects('/admin/users');

        $user = $this->users()->findOneBy(['email' => $email]);
        self::assertInstanceOf(User::class, $user);
        $this->removeUser((int) $user->getId());
    }

    public function testEditUpdatesUserAndPassword(): void
    {
        $this->loginAsAdmin();
        $user = $this->createUser(sprintf('editable.%s@example.com', uniqid()));
        $id = (int) $user->getId();

        $crawler = $this->client->request('GET', sprintf('/admin/users/%d/edit', $id));
        $this->assertResponseIsSuccessful();

        $token = (string) $crawler->filter('input[name="user[_token]"]')->attr('value');
        $this->client->request('POST', sprintf('/admin/users/%d/edit', $id), [
            'user' => [
                'email' => (string) $user->getEmail(),
                'name' => 'Renamed User',
                'roles' => ['ROLE_USER'],
                'plainPassword' => 'updatedpassword',
                '_token' => $token,
            ],
        ]);

        $this->assertResponseRedirects('/admin/users');
        $this->removeUser($id);
    }

    public function testDeletingYourselfIsBlocked(): void
    {
        $admin = $this->loginAsAdmin();
        $id = (int) $admin->getId();

        $this->client->request('POST', sprintf('/admin/users/%d/delete', $id), ['_token' => 'whatever']);

        $this->assertResponseRedirects('/admin/users');
        $this->entityManager()->clear();
        self::assertNotNull($this->users()->find($id));
    }

    public function testDeleteRemovesAnotherUserWithAValidToken(): void
    {
        $this->loginAsAdmin();
        $id = (int) $this->createUser(sprintf('deletable.%s@example.com', uniqid()))->getId();

        $crawler = $this->client->request('GET', sprintf('/admin/users/%d/edit', $id));
        $form = $crawler->filter('form[action$="/delete"]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/users');
        $this->entityManager()->clear();
        self::assertNull($this->users()->find($id));
    }

    public function testDeleteIgnoresAnInvalidToken(): void
    {
        $this->loginAsAdmin();
        $id = (int) $this->createUser(sprintf('keep.%s@example.com', uniqid()))->getId();

        $this->client->request('POST', sprintf('/admin/users/%d/delete', $id), ['_token' => 'invalid']);

        $this->assertResponseRedirects('/admin/users');
        $this->entityManager()->clear();
        self::assertNotNull($this->users()->find($id));
        $this->removeUser($id);
    }

    private function createUser(string $email): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setName('Temp User')
            ->setRoles(['ROLE_USER'])
            ->setPassword('not-a-real-hash');

        $em = $this->entityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function removeUser(int $id): void
    {
        $this->entityManager()->clear();
        $user = $this->users()->find($id);
        if (null !== $user) {
            $em = $this->entityManager();
            $em->remove($user);
            $em->flush();
        }
    }
}
