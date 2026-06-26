<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Contact;
use App\Tests\FunctionalTestCase;

final class ContactControllerTest extends FunctionalTestCase
{
    public function testNewCreatesContact(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/contacts/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('button.btn--primary')->form([
            'contact[name]' => 'Functional Tester',
            'contact[email]' => 'functional.tester@example.com',
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/contacts');

        $em = $this->entityManager();
        foreach ($this->contacts()->findBy(['name' => 'Functional Tester']) as $contact) {
            $em->remove($contact);
        }
        $em->flush();
    }

    public function testEditUpdatesContact(): void
    {
        $this->loginAsAdmin();
        $id = (string) $this->createContact('Editable Contact')->getId();

        $crawler = $this->client->request('GET', sprintf('/contacts/%s/edit', $id));
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('button.btn--primary')->form(['contact[name]' => 'Edited Contact']);
        $this->client->submit($form);

        $this->assertResponseRedirects('/contacts');
        $this->removeContact($id);
    }

    public function testDeleteRemovesContactWithAValidToken(): void
    {
        $this->loginAsAdmin();
        $id = (string) $this->createContact('Deletable Contact')->getId();

        $crawler = $this->client->request('GET', sprintf('/contacts/%s/edit', $id));
        $form = $crawler->filter('form[action$="/delete"]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects('/contacts');
        $this->entityManager()->clear();
        self::assertNull($this->contacts()->find($id));
    }

    public function testDeleteIgnoresAnInvalidToken(): void
    {
        $this->loginAsAdmin();
        $id = (string) $this->createContact('Surviving Contact')->getId();

        $this->client->request('POST', sprintf('/contacts/%s/delete', $id), ['_token' => 'invalid']);

        $this->assertResponseRedirects('/contacts');
        $this->entityManager()->clear();
        self::assertNotNull($this->contacts()->find($id));
        $this->removeContact($id);
    }

    private function createContact(string $name): Contact
    {
        $contact = (new Contact())->setName($name);
        $em = $this->entityManager();
        $em->persist($contact);
        $em->flush();

        return $contact;
    }

    private function removeContact(string $id): void
    {
        $this->entityManager()->clear();
        $contact = $this->contacts()->find($id);
        if (null !== $contact) {
            $em = $this->entityManager();
            $em->remove($contact);
            $em->flush();
        }
    }
}
