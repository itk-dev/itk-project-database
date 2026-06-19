<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Applies the locale chosen through {@see \App\Controller\LocaleController}
 * (stored in the session) to each request. Runs before Symfony's own
 * LocaleListener (priority 20) so the request locale is set in time.
 */
final readonly class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(private string $defaultLocale = 'da')
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        $locale = $request->getSession()->get('_locale');
        $request->setLocale(\is_string($locale) ? $locale : $this->defaultLocale);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
