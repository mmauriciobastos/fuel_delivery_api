<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber to automatically enable the tenant filter on each request.
 * This ensures that all database queries are automatically filtered by tenant.
 */
class TenantFilterSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TenantContext $tenantContext,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    /**
     * Enable the tenant filter on kernel request.
     * The filter will be enabled if a tenant context is set.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // Only process main requests
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Skip tenant filter for specific routes (e.g., health checks, public endpoints)
        $route = $request->attributes->get('_route');
        if ($this->shouldSkipTenantFilter($route)) {
            return;
        }

        // Enable the tenant filter
        try {
            $filter = $this->entityManager->getFilters()->enable('tenant_filter');

            // Set the tenant_id parameter if a tenant is available
            if ($this->tenantContext->hasTenant()) {
                $tenantId = $this->tenantContext->getCurrentTenantId();
                $filter->setParameter('tenant_id', $tenantId);

                $this->logger->debug('Tenant filter enabled', [
                    'tenant_id' => $tenantId,
                    'route' => $route,
                ]);
            } else {
                // If no tenant context is set, the filter will block all queries
                // This is a security measure to prevent data leaks
                $this->logger->warning('Tenant filter enabled without tenant context', [
                    'route' => $route,
                    'uri' => $request->getUri(),
                ]);
            }
        } catch (\Exception $e) {
            // Log the error but don't break the application
            $this->logger->error('Failed to enable tenant filter', [
                'error' => $e->getMessage(),
                'route' => $route,
            ]);
        }
    }

    /**
     * Determine if the tenant filter should be skipped for a given route.
     *
     * @param string|null $route The route name
     *
     * @return bool True if the filter should be skipped
     */
    private function shouldSkipTenantFilter(?string $route): bool
    {
        if (null === $route) {
            return false;
        }

        // Skip filter for specific routes
        $skipRoutes = [
            '_wdt',           // Web Debug Toolbar
            '_profiler',      // Symfony Profiler
            '_health',        // Health check endpoints
            'api_doc',        // API documentation
            'api_entrypoint', // API Platform entry point (might need tenant)
        ];

        foreach ($skipRoutes as $skipRoute) {
            if (str_starts_with($route, $skipRoute)) {
                return true;
            }
        }

        return false;
    }
}
