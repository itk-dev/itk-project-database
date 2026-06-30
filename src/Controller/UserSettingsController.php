<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserSettingsController extends AbstractController
{
    /**
     * Toggle the current user's mascot preference. Answers 204 to the mascot's
     * fetch (which handles the farewell/return animation itself) and otherwise
     * redirects back — so the plain <form> still works without JavaScript.
     */
    #[Route('/settings/mascot/toggle', name: 'app_settings_mascot_toggle', methods: ['POST'])]
    public function toggleMascot(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($user instanceof User
            && $this->isCsrfTokenValid('toggle-mascot', (string) $request->request->get('_token'))) {
            $user->setMascotEnabled(!$user->isMascotEnabled());
            $entityManager->flush();
        }

        if ('fetch' === $request->headers->get('X-Requested-With')) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        return $this->redirect($this->safeReturn($request));
    }

    /**
     * Toggle whether the completion stars fly into the trophy. A plain redirect:
     * the page re-renders with the new preference and the bar is unaffected.
     */
    #[Route('/settings/stars/toggle', name: 'app_settings_stars_toggle', methods: ['POST'])]
    public function toggleStars(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($user instanceof User
            && $this->isCsrfTokenValid('toggle-stars', (string) $request->request->get('_token'))) {
            $user->setStarsEnabled(!$user->isStarsEnabled());
            $entityManager->flush();
        }

        return $this->redirect($this->safeReturn($request));
    }

    /**
     * Only follow local, relative return paths to avoid open redirects (same
     * guard as the locale switcher).
     */
    private function safeReturn(Request $request): string
    {
        $return = (string) $request->request->get('return', '');
        if ('' !== $return
            && str_starts_with($return, '/')
            && !str_starts_with($return, '//')
            && !str_contains($return, '\\')) {
            return $return;
        }

        return $this->generateUrl('app_dashboard');
    }
}
