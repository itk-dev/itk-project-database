<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Initiative;
use App\Entity\User;
use App\Enum\Status;
use App\Repository\ContactRepository;
use App\Repository\InitiativeRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Pushes a single Mercure payload describing an initiative change to every
 * connected client: a Turbo Stream that prepends the change to the live activity
 * feed and refreshes the dashboard's stats, recent list and status bars. The
 * counts are recomputed here so the broadcast reflects the state after flush.
 */
final class ActivityPublisher
{
    /**
     * Must match the topic the dashboard subscribes to via turbo_stream_from().
     */
    public const string TOPIC = 'activities';

    public function __construct(
        private readonly HubInterface $hub,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly InitiativeRepository $initiatives,
        private readonly ContactRepository $contacts,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function publish(string $action, Initiative $initiative, ?User $actor): void
    {
        // A deleted initiative has no page left to link to.
        $url = 'deleted' === $action
            ? null
            : $this->urlGenerator->generate('app_initiative_show', ['id' => $initiative->getId()]);

        $stream = $this->twig->render('activity/_broadcast.html.twig', [
            'action' => $action,
            'initiativeId' => (string) $initiative->getId(),
            'title' => $initiative->getTitle(),
            'url' => $url,
            'actor' => $actor?->getName(),
            'at' => new \DateTimeImmutable(),
            'total' => $this->initiatives->countAll(),
            'byStatus' => $this->initiatives->countByStatus(),
            'statuses' => Status::cases(),
            'recent' => $this->initiatives->findRecent(8),
            'contactCount' => $this->contacts->count([]),
        ]);

        try {
            $this->hub->publish(new Update(self::TOPIC, $stream));
        } catch (\Throwable $e) {
            // A live-broadcast failure (e.g. the Mercure hub being unreachable) must
            // never break the underlying save — autosave is the primary save path.
            $this->logger->warning('Failed to publish activity update: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }
}
