<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Initiative;
use App\Repository\ContactRepository;
use App\Repository\InitiativeRepository;
use App\Service\ActivityPublisher;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class ActivityPublisherTest extends TestCase
{
    public function testPublishSwallowsHubFailuresAndLogsThem(): void
    {
        $hub = $this->createStub(HubInterface::class);
        $hub->method('publish')->willThrowException(new \RuntimeException('hub unreachable'));

        $twig = $this->createStub(Environment::class);
        $twig->method('render')->willReturn('<turbo-stream></turbo-stream>');

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/initiatives/1');

        $initiatives = $this->createStub(InitiativeRepository::class);
        $initiatives->method('countAll')->willReturn(1);
        $initiatives->method('countByStatus')->willReturn([]);
        $initiatives->method('findRecent')->willReturn([]);

        $contacts = $this->createStub(ContactRepository::class);
        $contacts->method('count')->willReturn(0);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $publisher = new ActivityPublisher($hub, $twig, $urlGenerator, $initiatives, $contacts, $logger);

        // An unreachable hub must be swallowed and logged, never bubbled up — the
        // underlying save (autosave) is the primary path and must still succeed.
        $publisher->publish('created', (new Initiative())->setTitle('Broadcast me'), null);
    }
}
