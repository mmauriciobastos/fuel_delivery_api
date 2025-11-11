<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Client;
use App\Service\TenantContext;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist)]
readonly class TenantEntitySubscriber
{
    public function __construct(
        private TenantContext $tenantContext
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        // Only process tenant-aware entities that don't already have a tenant set
        if (!$entity instanceof Client) {
            return;
        }

        if (null !== $entity->getTenant()) {
            return;
        }

        if (!$this->tenantContext->hasTenant()) {
            return;
        }

        $entity->setTenant($this->tenantContext->getCurrentTenant());
    }
}
