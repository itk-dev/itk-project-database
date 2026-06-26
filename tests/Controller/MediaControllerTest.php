<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Initiative;
use App\Entity\InitiativeAttachment;
use App\Entity\InitiativeImage;
use App\Tests\FunctionalTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MediaControllerTest extends FunctionalTestCase
{
    public function testImageIsServedToAuthenticatedUsers(): void
    {
        $this->loginAsAdmin();
        $initiative = $this->anyInitiative();

        $image = (new InitiativeImage())->setAlt('Test image');
        $image->setImageFile($this->upload('sample.png', 'image/png', $this->pngBytes()));
        $initiative->addImage($image);
        $em = $this->entityManager();
        $em->flush();

        $this->client->request('GET', sprintf('/media/image/%s', (string) $image->getId()));
        $this->assertResponseIsSuccessful();

        $em->remove($image);
        $em->flush();
    }

    public function testAttachmentIsServedToAuthenticatedUsers(): void
    {
        $this->loginAsAdmin();
        $initiative = $this->anyInitiative();

        $attachment = new InitiativeAttachment();
        $attachment->setFile($this->upload('document.pdf', 'application/pdf', "%PDF-1.4\n%%EOF\n"));
        $initiative->addAttachment($attachment);
        $em = $this->entityManager();
        $em->flush();

        $this->client->request('GET', sprintf('/media/attachment/%s', (string) $attachment->getId()));
        $this->assertResponseIsSuccessful();

        $em->remove($attachment);
        $em->flush();
    }

    private function anyInitiative(): Initiative
    {
        $initiative = $this->initiatives()->findOneBy([]);
        self::assertInstanceOf(Initiative::class, $initiative, 'Fixtures should provide at least one initiative.');

        return $initiative;
    }

    private function upload(string $name, string $mimeType, string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'itk');
        self::assertIsString($path);
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, $mimeType, null, true);
    }

    private function pngBytes(): string
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', true);
        self::assertIsString($png);

        return $png;
    }
}
