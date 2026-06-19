<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\InitiativeAttachment;
use App\Entity\InitiativeImage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Vich\UploaderBundle\Handler\DownloadHandler;

/**
 * Serves privately stored uploads. Sits under the ROLE_USER firewall, so
 * files are never publicly reachable.
 */
class MediaController extends AbstractController
{
    public function __construct(private readonly DownloadHandler $downloadHandler)
    {
    }

    #[Route('/media/image/{id}', name: 'app_media_image', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function image(InitiativeImage $image): Response
    {
        return $this->downloadHandler->downloadObject($image, 'imageFile', InitiativeImage::class, $image->getOriginalName(), false);
    }

    #[Route('/media/attachment/{id}', name: 'app_media_attachment', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function attachment(InitiativeAttachment $attachment): Response
    {
        return $this->downloadHandler->downloadObject($attachment, 'file', InitiativeAttachment::class, $attachment->getOriginalName() ?? 'attachment', true);
    }
}
