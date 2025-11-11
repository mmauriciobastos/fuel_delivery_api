<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Embeddable\Address;
use App\Repository\ClientRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: 'clients')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_client_tenant')]
#[ORM\Index(columns: ['company_name'], name: 'idx_client_company_name')]
#[ORM\Index(columns: ['email'], name: 'idx_client_email')]
#[ORM\Index(columns: ['is_active'], name: 'idx_client_is_active')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_DISPATCHER')"),
        new Get(security: "is_granted('ROLE_USER')"),
        new Patch(security: "is_granted('ROLE_DISPATCHER')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['client:read']],
    denormalizationContext: ['groups' => ['client:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 30
)]
#[ApiFilter(SearchFilter::class, properties: [
    'companyName' => 'partial',
    'contactName' => 'partial',
    'email' => 'partial',
    'phone' => 'partial',
    'isActive' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: [
    'companyName',
    'contactName',
    'email',
    'createdAt',
])]
class Client
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['client:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['client:read'])]
    private ?Tenant $tenant = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'Company name is required')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Company name must be at least {{ limit }} characters long',
        maxMessage: 'Company name cannot be longer than {{ limit }} characters'
    )]
    #[Groups(['client:read', 'client:write'])]
    private ?string $companyName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'Contact name is required')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Contact name must be at least {{ limit }} characters long',
        maxMessage: 'Contact name cannot be longer than {{ limit }} characters'
    )]
    #[Groups(['client:read', 'client:write'])]
    private ?string $contactName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Email(message: 'The email "{{ value }}" is not a valid email address')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Email cannot be longer than {{ limit }} characters'
    )]
    #[Groups(['client:read', 'client:write'])]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(
        max: 50,
        maxMessage: 'Phone number cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^[\d\s\-\+\(\)]+$/',
        message: 'Phone number can only contain digits, spaces, hyphens, plus signs, and parentheses'
    )]
    #[Groups(['client:read', 'client:write'])]
    private ?string $phone = null;

    #[ORM\Embedded(class: Address::class, columnPrefix: 'billing_')]
    #[Groups(['client:read', 'client:write'])]
    private ?Address $billingAddress = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    #[Groups(['client:read', 'client:write'])]
    #[SerializedName('isActive')]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['client:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['client:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->billingAddress = new Address();
        $this->isActive = true;
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

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function setTenant(?Tenant $tenant): static
    {
        $this->tenant = $tenant;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(string $contactName): static
    {
        $this->contactName = $contactName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?Address $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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
     * Activate the client.
     */
    public function activate(): static
    {
        $this->isActive = true;

        return $this;
    }

    /**
     * Deactivate the client (soft delete).
     */
    public function deactivate(): static
    {
        $this->isActive = false;

        return $this;
    }
}
