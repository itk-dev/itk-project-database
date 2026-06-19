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
        if ($request->hasSession()) {
            $request->getSession()->set('_locale', $locale);
        }

        $return = (string) $request->query->get('return', '');

        // Only follow local, relative return paths to avoid open redirects.
        if ('' !== $return && str_starts_with($return, '/') && !str_starts_with($return, '//')) {
            return $this->redirect($return);
        }

        return $this->redirectToRoute('app_dashboard');
    }
}
