<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\Partner;
use App\Tests\FunctionalTestCase;

final class PartnerControllerTest extends FunctionalTestCase
{
    public function testIndexIsAccessibleToEditors(): void
    {
        $this->loginAsEditor();
        $this->client->request('GET', '/admin/partners');

        $this->assertResponseIsSuccessful();
    }

    public function testNewCreatesPartner(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/partners/new');
        $this->assertResponseIsSuccessful();

        $name = 'Test Partner '.uniqid();
        $form = $crawler->filter('button.btn--primary')->form([
            'partner[name]' => $name,
            'partner[description]' => 'A partner created in a test.',
            'partner[website]' => 'https://example.com',
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/partners');

        $partner = $this->partners()->findOneBy(['name' => $name]);
        self::assertInstanceOf(Partner::class, $partner);
        self::assertSame('A partner created in a test.', $partner->getDescription());
        self::assertSame('https://example.com', $partner->getWebsite());

        $this->removePartner((string) $partner->getId());
    }

    public function testNewRejectsADuplicateName(): void
    {
        $this->loginAsAdmin();
        $name = 'Duplicate Partner '.uniqid();
        $id = (string) $this->createPartner($name)->getId();

        $crawler = $this->client->request('GET', '/admin/partners/new');
        $form = $crawler->filter('button.btn--primary')->form(['partner[name]' => $name]);
        $this->client->submit($form);

        // UniqueEntity rejects the second one: the form redisplays with a 422
        // (Symfony's status for an invalid submitted form) and nothing is saved.
        $this->assertResponseStatusCodeSame(422);
        self::assertCount(1, $this->partners()->findBy(['name' => $name]));

        $this->removePartner($id);
    }

    public function testNewRejectsANonHttpWebsite(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/partners/new');

        $name = 'Bad Website Partner '.uniqid();
        $form = $crawler->filter('button.btn--primary')->form([
            'partner[name]' => $name,
            'partner[website]' => 'javascript:alert(1)',
        ]);
        $this->client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        self::assertCount(0, $this->partners()->findBy(['name' => $name]));
    }

    public function testEditUpdatesPartner(): void
    {
        $this->loginAsAdmin();
        $id = (string) $this->createPartner('Editable Partner '.uniqid())->getId();

        $crawler = $this->client->request('GET', sprintf('/admin/partners/%s/edit', $id));
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('button.btn--primary')->form(['partner[name]' => 'Edited Partner '.uniqid()]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/partners');
        $this->removePartner($id);
    }

    public function testDeleteRemovesPartnerWithAValidToken(): void
    {
        $this->loginAsAdmin();
        $id = (string) $this->createPartner('Deletable Partner '.uniqid())->getId();

        $crawler = $this->client->request('GET', sprintf('/admin/partners/%s/edit', $id));
        $form = $crawler->filter('form[action$="/delete"]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/partners');
        $this->entityManager()->clear();
        self::assertNull($this->partners()->find($id));
    }

    public function testDeleteIgnoresAnInvalidToken(): void
    {
        $this->loginAsAdmin();
        $id = (string) $this->createPartner('Surviving Partner '.uniqid())->getId();

        $this->client->request('POST', sprintf('/admin/partners/%s/delete', $id), ['_token' => 'invalid']);

        $this->assertResponseRedirects('/admin/partners');
        $this->entityManager()->clear();
        self::assertNotNull($this->partners()->find($id));
        $this->removePartner($id);
    }

    private function createPartner(string $name): Partner
    {
        $partner = (new Partner())->setName($name);
        $em = $this->entityManager();
        $em->persist($partner);
        $em->flush();

        return $partner;
    }

    private function removePartner(string $id): void
    {
        $this->entityManager()->clear();
        $partner = $this->partners()->find($id);
        if (null !== $partner) {
            $em = $this->entityManager();
            $em->remove($partner);
            $em->flush();
        }
    }
}
