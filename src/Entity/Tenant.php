<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Enum\TenantStatus;
use App\Repository\TenantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
#[ORM\Table(name: 'tenants')]
#[ORM\Index(columns: ['subdomain'], name: 'idx_tenant_subdomain')]
#[ORM\Index(columns: ['status'], name: 'idx_tenant_status')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['subdomain'], message: 'This subdomain is already in use.')]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_ADMIN')",
        ),
        new Post(
            security: "is_granted('ROLE_ADMIN')",
        ),
        new Get(
            security: "is_granted('ROLE_ADMIN')",
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN')",
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')",
        ),
    ],
    normalizationContext: ['groups' => ['tenant:read']],
    denormalizationContext: ['groups' => ['tenant:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 30
)]
class Tenant
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['tenant:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'Tenant name is required')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Tenant name must be at least {{ limit }} characters long',
        maxMessage: 'Tenant name cannot be longer than {{ limit }} characters'
    )]
    #[Groups(['tenant:read', 'tenant:write'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true, nullable: false)]
    #[Assert\NotBlank(message: 'Subdomain is required')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'Subdomain must be at least {{ limit }} characters long',
        maxMessage: 'Subdomain cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        message: 'Subdomain must contain only lowercase letters, numbers, and hyphens (no consecutive hyphens)'
    )]
    #[Groups(['tenant:read', 'tenant:write'])]
    private ?string $subdomain = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: TenantStatus::class)]
    #[Assert\NotNull(message: 'Status is required')]
    #[Groups(['tenant:read', 'tenant:write'])]
    private ?TenantStatus $status = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['tenant:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['tenant:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->status = TenantStatus::TRIAL;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSubdomain(): ?string
    {
        return $this->subdomain;
    }

    public function setSubdomain(string $subdomain): static
    {
        $this->subdomain = strtolower(trim($subdomain));

        return $this;
    }

    public function getStatus(): ?TenantStatus
    {
        return $this->status;
    }

    public function setStatus(TenantStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Check if tenant is active and can perform operations
     */
    public function isOperational(): bool
    {
        return $this->status !== null && $this->status->isOperational();
    }

    /**
     * Activate the tenant
     */
    public function activate(): static
    {
        $this->status = TenantStatus::ACTIVE;

        return $this;
    }

    /**
     * Suspend the tenant
     */
    public function suspend(): static
    {
        $this->status = TenantStatus::SUSPENDED;

        return $this;
    }

    /**
     * Convert tenant to trial status
     */
    public function convertToTrial(): static
    {
        $this->status = TenantStatus::TRIAL;

        return $this;
    }
}
