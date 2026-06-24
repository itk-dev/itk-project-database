<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\SecurityHeadersSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class SecurityHeadersSubscriberTest extends TestCase
{
    public function testSubscribesToKernelResponse(): void
    {
        self::assertArrayHasKey(KernelEvents::RESPONSE, SecurityHeadersSubscriber::getSubscribedEvents());
    }

    public function testSetsHardeningHeadersAndCspOutsideDebug(): void
    {
        $response = $this->handle(new SecurityHeadersSubscriber(debug: false), HttpKernelInterface::MAIN_REQUEST);

        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertSame('DENY', $response->headers->get('X-Frame-Options'));
        self::assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        self::assertStringContainsString("default-src 'self'", (string) $response->headers->get('Content-Security-Policy'));
    }

    public function testOmitsCspInDebug(): void
    {
        $response = $this->handle(new SecurityHeadersSubscriber(debug: true), HttpKernelInterface::MAIN_REQUEST);

        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertFalse($response->headers->has('Content-Security-Policy'));
    }

    public function testIgnoresSubRequests(): void
    {
        $response = $this->handle(new SecurityHeadersSubscriber(debug: false), HttpKernelInterface::SUB_REQUEST);

        self::assertFalse($response->headers->has('X-Content-Type-Options'));
    }

    private function handle(SecurityHeadersSubscriber $subscriber, int $requestType): Response
    {
        $response = new Response();
        $event = new ResponseEvent($this->createMock(HttpKernelInterface::class), new Request(), $requestType, $response);

        $subscriber->onKernelResponse($event);

        return $response;
    }
}
