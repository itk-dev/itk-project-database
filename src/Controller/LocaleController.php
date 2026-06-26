<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocaleController extends AbstractController
{
    #[Route('/locale/{locale}', name: 'app_locale', requirements: ['locale' => 'en|da'])]
    public function switch(string $locale, Request $request): Response
    {
        // Persist the choice only when a session already exists, so an
        // anonymous hit to this public route does not allocate one. The
        // language switcher is only shown to authenticated users, who always
        // carry a session.
        if ($request->hasPreviousSession()) {
            $request->getSession()->set('_locale', $locale);
        }

        $return = (string) $request->query->get('return', '');

        // Only follow local, relative return paths to avoid open redirects.
        // Reject protocol-relative (//host) and backslash variants (/\host),
        // which some browsers normalise to //host.
        if ('' !== $return
            && str_starts_with($return, '/')
            && !str_starts_with($return, '//')
            && !str_contains($return, '\\')) {
            return $this->redirect($return);
        }

        return $this->redirectToRoute('app_dashboard');
    }
}
