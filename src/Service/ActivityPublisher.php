<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Initiative;
use App\Entity\User;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Pushes a Turbo Stream describing an initiative change to the Mercure hub so
 * every connected client prepends it to the live activity feed.
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
    ) {
    }

    public function publish(string $action, Initiative $initiative, ?User $actor): void
    {
        // A deleted initiative has no page left to link to.
        $url = 'deleted' === $action
            ? null
            : $this->urlGenerator->generate('app_initiative_show', ['id' => $initiative->getId()]);

        $stream = $this->twig->render('activity/_stream.html.twig', [
            'action' => $action,
            'title' => $initiative->getTitle(),
            'url' => $url,
            'actor' => $actor?->getName(),
            'at' => new \DateTimeImmutable(),
        ]);

        $this->hub->publish(new Update(self::TOPIC, $stream));
    }
}
