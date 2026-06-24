<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\LocaleSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class LocaleSubscriberTest extends TestCase
{
    public function testSubscribesToKernelRequest(): void
    {
        self::assertArrayHasKey(KernelEvents::REQUEST, LocaleSubscriber::getSubscribedEvents());
    }

    public function testAppliesStoredLocaleWhenSessionExists(): void
    {
        $request = $this->requestWithPreviousSession(['_locale' => 'en']);
        $this->dispatch($request);

        self::assertSame('en', $request->getLocale());
    }

    public function testFallsBackToDefaultLocaleWhenNoneStored(): void
    {
        $request = $this->requestWithPreviousSession([]);
        $this->dispatch($request);

        self::assertSame('da', $request->getLocale());
    }

    public function testDoesNothingWithoutAPreviousSession(): void
    {
        $request = new Request();
        $request->setLocale('en');
        $this->dispatch($request);

        self::assertSame('en', $request->getLocale());
    }

    /**
     * @param array<string, mixed> $sessionData
     */
    private function requestWithPreviousSession(array $sessionData): Request
    {
        $session = new Session(new MockArraySessionStorage());
        foreach ($sessionData as $key => $value) {
            $session->set($key, $value);
        }

        $request = new Request();
        $request->setSession($session);
        // A matching session cookie makes Request::hasPreviousSession() return true.
        $request->cookies->set($session->getName(), 'test');

        return $request;
    }

    private function dispatch(Request $request): void
    {
        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        (new LocaleSubscriber())->onKernelRequest($event);
    }
}
