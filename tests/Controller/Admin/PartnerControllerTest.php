<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\Initiative;
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

    public function testIndexDeleteDialogLinksTheAffectedInitiatives(): void
    {
        $this->loginAsAdmin();
        $partner = $this->createPartner('Linked Partner '.uniqid());
        $initiative = $this->createInitiativeUsing($partner, 'Linked Initiative '.uniqid());

        $crawler = $this->client->request('GET', '/admin/partners');
        $this->assertResponseIsSuccessful();

        // The confirmation has to name what deleting would strip the partner off, and
        // link straight to it — the count alone doesn't tell the admin what breaks.
        $link = $crawler->filter(sprintf('dialog a[href="/initiatives/%s"]', $initiative->getId()));
        self::assertCount(1, $link);
        self::assertSame($initiative->getTitle(), trim($link->text()));

        $this->removeInitiative((string) $initiative->getId());
        $this->removePartner((string) $partner->getId());
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

    public function testNewRejectsANameContainingAComma(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/partners/new');

        // Comma is the separator of the free-tagging field on the initiative form,
        // so such a name would later be split into two partners.
        $name = 'Aarhus Kommune, Teknik og Miljø '.uniqid();
        $form = $crawler->filter('button.btn--primary')->form(['partner[name]' => $name]);
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

    public function testDeleteDetachesThePartnerButKeepsTheInitiative(): void
    {
        $this->loginAsAdmin();
        $partner = $this->createPartner('Detachable Partner '.uniqid());
        $partnerId = (string) $partner->getId();
        $initiative = $this->createInitiativeUsing($partner, 'Surviving Initiative '.uniqid());
        $initiativeId = (string) $initiative->getId();

        $crawler = $this->client->request('GET', sprintf('/admin/partners/%s/edit', $partnerId));
        self::assertStringContainsString($initiative->getTitle(), (string) $this->client->getResponse()->getContent());

        $this->client->submit($crawler->filter('form[action$="/delete"]')->form());
        $this->assertResponseRedirects('/admin/partners');

        // The join table is cleared by its ON DELETE CASCADE rather than by Doctrine,
        // so pin both halves: the partner is gone, the initiative is not.
        $this->entityManager()->clear();
        self::assertNull($this->partners()->find($partnerId));
        $survivor = $this->initiatives()->find($initiativeId);
        self::assertInstanceOf(Initiative::class, $survivor);
        self::assertCount(0, $survivor->getPartners());

        $this->removeInitiative($initiativeId);
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

    public function testInitiativesAreSearchableByPartnerName(): void
    {
        $this->loginAsEditor();
        $partner = $this->createPartner('Searchable Partner '.uniqid());
        $initiative = $this->createInitiativeUsing($partner, 'Findable Initiative '.uniqid());

        $crawler = $this->client->request('GET', '/initiatives?q='.urlencode((string) $partner->getName()));

        $this->assertResponseIsSuccessful();
        self::assertStringContainsString((string) $initiative->getTitle(), $crawler->filter('#initiative-results')->text());

        $this->removeInitiative((string) $initiative->getId());
        $this->removePartner((string) $partner->getId());
    }

    public function testTheDeleteTriggerCannotSubmitOnItsOwn(): void
    {
        $this->loginAsAdmin();
        $id = (string) $this->createPartner('Guarded Partner '.uniqid())->getId();

        $crawler = $this->client->request('GET', sprintf('/admin/partners/%s/edit', $id));

        // The only submit lives inside the dialog, so a click that lands before
        // Stimulus has hydrated cannot delete anything.
        $buttons = $crawler->filter('form[action$="/delete"] button');
        self::assertSame('button', $buttons->eq(0)->attr('type'));
        self::assertCount(1, $crawler->filter('form[action$="/delete"] button[type="submit"]'));
        self::assertCount(1, $crawler->filter('form[action$="/delete"] dialog button[type="submit"]'));

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

    private function createInitiativeUsing(Partner $partner, string $title): Initiative
    {
        $initiative = (new Initiative())->setTitle($title);
        $initiative->addPartner($partner);
        $em = $this->entityManager();
        $em->persist($initiative);
        $em->flush();

        return $initiative;
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

    private function removeInitiative(string $id): void
    {
        $this->entityManager()->clear();
        $initiative = $this->initiatives()->find($id);
        if (null !== $initiative) {
            $em = $this->entityManager();
            $em->remove($initiative);
            $em->flush();
        }
    }
}
