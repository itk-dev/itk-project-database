<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\BlameableInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Records the logged-in user as creator (on insert) and modifier (on every
 * insert/update) for any {@see BlameableInterface} entity. Without an
 * authenticated user — CLI, fixtures — it leaves the columns null.
 */
#[AsDoctrineListener(event: Events::onFlush)]
final class BlameableListener
{
    public function __construct(private readonly Security $security)
    {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof UserInterface) {
            return;
        }

        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof BlameableInterface) {
                continue;
            }
            if (null === $entity->getCreatedBy()) {
                $entity->setCreatedBy($user);
            }
            $entity->setModifiedBy($user);
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata($entity::class), $entity);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof BlameableInterface) {
                continue;
            }
            $entity->setModifiedBy($user);
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata($entity::class), $entity);
        }
    }
}
