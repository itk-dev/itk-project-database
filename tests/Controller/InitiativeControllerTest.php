<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Initiative;
use App\Entity\InitiativeAttachment;
use App\Tests\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Response;

final class InitiativeControllerTest extends FunctionalTestCase
{
    public function testNewPersistsInitiativeWithInlineContactAndDropsEmptyMedia(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/initiatives/new');
        $this->assertResponseIsSuccessful();

        $token = (string) $crawler->filter('input[name="initiative[_token]"]')->attr('value');
        $this->client->request('POST', '/initiatives/new', [
            'initiative' => [
                'title' => 'Coverage initiative',
                'newContacts' => [['name' => 'Coverage Contact']],
                // An empty image row exercises the image branch of removeEmptyMedia().
                'images' => [['alt' => 'empty image row']],
                '_token' => $token,
            ],
        ]);

        $this->assertResponseRedirects();

        $em = $this->entityManager();
        $initiative = $this->initiatives()->findOneBy(['title' => 'Coverage initiative']);
        self::assertInstanceOf(Initiative::class, $initiative);
        self::assertCount(0, $initiative->getImages(), 'Empty image rows should be dropped.');
        self::assertGreaterThanOrEqual(1, $initiative->getContacts()->count(), 'Inline contact should be merged in.');

        $em->remove($initiative);
        $em->flush();

        foreach ($this->contacts()->findBy(['name' => 'Coverage Contact']) as $contact) {
            $em->remove($contact);
        }
        $em->flush();
    }

    public function testEditUpdatesInitiative(): void
    {
        $this->loginAsAdmin();
        $initiative = $this->createInitiative('Editable initiative');
        $id = (int) $initiative->getId();

        $crawler = $this->client->request('GET', sprintf('/initiatives/%d/edit', $id));
        $this->assertResponseIsSuccessful();

        $token = (string) $crawler->filter('input[name="initiative[_token]"]')->attr('value');
        $this->client->request('POST', sprintf('/initiatives/%d/edit', $id), [
            'initiative' => [
                'title' => 'Edited initiative',
                'images' => [['alt' => 'empty']],
                'attachments' => [[]],
                '_token' => $token,
            ],
        ]);

        $this->assertResponseRedirects(sprintf('/initiatives/%d', $id));

        $this->removeInitiative($id);
    }

    public function testDeleteRemovesInitiativeWithAValidToken(): void
    {
        $this->loginAsAdmin();
        $initiative = $this->createInitiative('Deletable initiative');
        $id = (int) $initiative->getId();

        $crawler = $this->client->request('GET', sprintf('/initiatives/%d/edit', $id));
        $form = $crawler->filter('form[action$="/delete"]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects('/initiatives');

        $this->entityManager()->clear();
        self::assertNull($this->initiatives()->find($id));
    }

    public function testDeleteIgnoresAnInvalidToken(): void
    {
        $this->loginAsAdmin();
        $initiative = $this->createInitiative('Survivor initiative');
        $id = (int) $initiative->getId();

        $this->client->request('POST', sprintf('/initiatives/%d/delete', $id), ['_token' => 'invalid']);

        $this->assertResponseRedirects('/initiatives');
        $this->entityManager()->clear();
        self::assertNotNull($this->initiatives()->find($id));

        $this->removeInitiative($id);
    }

    public function testEditDropsAttachmentsLeftWithoutAFile(): void
    {
        $this->loginAsAdmin();
        $initiative = $this->createInitiative('Has empty attachment');
        $initiative->addAttachment(new InitiativeAttachment());
        $em = $this->entityManager();
        $em->flush();
        $id = (int) $initiative->getId();

        $crawler = $this->client->request('GET', sprintf('/initiatives/%d/edit', $id));
        $token = (string) $crawler->filter('input[name="initiative[_token]"]')->attr('value');
        $this->client->request('POST', sprintf('/initiatives/%d/edit', $id), [
            'initiative' => [
                'title' => 'Has empty attachment',
                // Re-submit the file-less attachment (empty file, no upload) so the
                // form keeps it; the controller's removeEmptyMedia() then drops it.
                'attachments' => [['file' => '']],
                '_token' => $token,
            ],
        ]);

        $this->assertResponseRedirects(sprintf('/initiatives/%d', $id));

        $this->entityManager()->clear();
        $reloaded = $this->initiatives()->find($id);
        self::assertNotNull($reloaded);
        self::assertCount(0, $reloaded->getAttachments(), 'A file-less attachment should be dropped.');

        $this->removeInitiative($id);
    }

    public function testNewAutosaveReturnsCreatedWithLocationHeader(): void
    {
        $this->loginAsAdmin();

        // Autosave posts via fetch with the X-Autosave header and no CSRF token.
        $this->client->request('POST', '/initiatives/new', [
            'initiative' => ['title' => 'Autosaved initiative'],
        ], [], ['HTTP_X-Autosave' => '1']);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertTrue($this->client->getResponse()->headers->has('X-Initiative-Location'));

        $initiative = $this->initiatives()->findOneBy(['title' => 'Autosaved initiative']);
        self::assertInstanceOf(Initiative::class, $initiative);

        $this->removeInitiative((int) $initiative->getId());
    }

    public function testNewAutosaveReturnsUnprocessableWhenInvalid(): void
    {
        $this->loginAsAdmin();

        // An empty title fails NotBlank, so autosave reports it without creating anything.
        $this->client->request('POST', '/initiatives/new', [
            'initiative' => ['title' => ''],
        ], [], ['HTTP_X-Autosave' => '1']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertNull($this->initiatives()->findOneBy(['title' => '']));
    }

    public function testEditAutosaveReturnsNoContent(): void
    {
        $this->loginAsAdmin();
        $initiative = $this->createInitiative('Autosave edit');
        $id = (int) $initiative->getId();

        $this->client->request('POST', sprintf('/initiatives/%d/edit', $id), [
            'initiative' => ['title' => 'Autosave edited'],
        ], [], ['HTTP_X-Autosave' => '1']);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->removeInitiative($id);
    }

    public function testEditAutosaveReturnsUnprocessableWhenInvalid(): void
    {
        $this->loginAsAdmin();
        $initiative = $this->createInitiative('Autosave edit invalid');
        $id = (int) $initiative->getId();

        $this->client->request('POST', sprintf('/initiatives/%d/edit', $id), [
            'initiative' => ['title' => ''],
        ], [], ['HTTP_X-Autosave' => '1']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->removeInitiative($id);
    }

    private function createInitiative(string $title): Initiative
    {
        $initiative = (new Initiative())->setTitle($title);
        $em = $this->entityManager();
        $em->persist($initiative);
        $em->flush();

        return $initiative;
    }

    private function removeInitiative(int $id): void
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
