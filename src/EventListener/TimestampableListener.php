<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\TimestampableInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Stamps createdAt on insert and updatedAt on every insert/update for any
 * {@see TimestampableInterface} entity. Runs in onFlush so the change set is
 * recomputed and the new values reach the database in the same flush.
 */
#[AsDoctrineListener(event: Events::onFlush)]
final class TimestampableListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();
        $now = new \DateTimeImmutable();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof TimestampableInterface) {
                continue;
            }
            if (null === $entity->getCreatedAt()) {
                $entity->setCreatedAt($now);
            }
            $entity->setUpdatedAt($now);
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata($entity::class), $entity);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof TimestampableInterface) {
                continue;
            }
            $entity->setUpdatedAt($now);
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata($entity::class), $entity);
        }
    }
}
