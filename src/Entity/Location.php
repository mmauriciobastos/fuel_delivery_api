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
use App\Repository\LocationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ORM\Table(name: 'locations')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_location_tenant')]
#[ORM\Index(columns: ['client_id'], name: 'idx_location_client')]
#[ORM\Index(columns: ['is_primary'], name: 'idx_location_is_primary')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_DISPATCHER')"),
        new Get(security: "is_granted('ROLE_USER')"),
        new Patch(security: "is_granted('ROLE_DISPATCHER')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['location:read']],
    denormalizationContext: ['groups' => ['location:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 30
)]
#[ApiFilter(SearchFilter::class, properties: [
    'client' => 'exact',
    'city' => 'partial',
    'state' => 'exact',
    'postalCode' => 'partial',
    'isPrimary' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: [
    'city',
    'state',
    'isPrimary',
    'createdAt',
])]
class Location
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['location:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['location:read'])]
    private ?Tenant $tenant = null;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Client is required')]
    #[Groups(['location:read', 'location:write'])]
    private ?Client $client = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'Address line 1 is required')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Address must be at least {{ limit }} characters long',
        maxMessage: 'Address cannot be longer than {{ limit }} characters'
    )]
    #[Groups(['location:read', 'location:write'])]
    private ?string $addressLine1 = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Address line 2 cannot be longer than {{ limit }} characters'
    )]
    #[Groups(['location:read', 'location:write'])]
    private ?string $addressLine2 = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: false)]
    #[Assert\NotBlank(message: 'City is required')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'City must be at least {{ limit }} characters long',
        maxMessage: 'City cannot be longer than {{ limit }} characters'
    )]
    #[Groups(['location:read', 'location:write'])]
    private ?string $city = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: false)]
    #[Assert\NotBlank(message: 'State/Province is required')]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'State must be at least {{ limit }} characters long',
        maxMessage: 'State cannot be longer than {{ limit }} characters'
    )]
    #[Groups(['location:read', 'location:write'])]
    private ?string $state = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: false)]
    #[Assert\NotBlank(message: 'Postal code is required')]
    #[Assert\Length(
        min: 3,
        max: 20,
        minMessage: 'Postal code must be at least {{ limit }} characters long',
        maxMessage: 'Postal code cannot be longer than {{ limit }} characters'
    )]
    #[Groups(['location:read', 'location:write'])]
    private ?string $postalCode = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: false, options: ['default' => 'Canada'])]
    #[Assert\NotBlank(message: 'Country is required')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Country must be at least {{ limit }} characters long',
        maxMessage: 'Country cannot be longer than {{ limit }} characters'
    )]
    #[Groups(['location:read', 'location:write'])]
    private string $country = 'Canada';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
    #[Assert\Range(
        min: -90,
        max: 90,
        notInRangeMessage: 'Latitude must be between {{ min }} and {{ max }}'
    )]
    #[Groups(['location:read', 'location:write'])]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
    #[Assert\Range(
        min: -180,
        max: 180,
        notInRangeMessage: 'Longitude must be between {{ min }} and {{ max }}'
    )]
    #[Groups(['location:read', 'location:write'])]
    private ?string $longitude = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['location:read', 'location:write'])]
    private ?string $specialInstructions = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    #[Groups(['location:read', 'location:write'])]
    private bool $isPrimary = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['location:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['location:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

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

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(string $addressLine1): static
    {
        $this->addressLine1 = $addressLine1;

        return $this;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(?string $addressLine2): static
    {
        $this->addressLine2 = $addressLine2;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getSpecialInstructions(): ?string
    {
        return $this->specialInstructions;
    }

    public function setSpecialInstructions(?string $specialInstructions): static
    {
        $this->specialInstructions = $specialInstructions;

        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function getIsPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): static
    {
        $this->isPrimary = $isPrimary;

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
     * Mark this location as the primary one.
     */
    public function markAsPrimary(): static
    {
        $this->isPrimary = true;

        return $this;
    }

    /**
     * Unmark this location as primary.
     */
    public function unmarkAsPrimary(): static
    {
        $this->isPrimary = false;

        return $this;
    }

    /**
     * Get the full formatted address.
     */
    public function getFormattedAddress(): string
    {
        $parts = [$this->addressLine1];

        if ($this->addressLine2) {
            $parts[] = $this->addressLine2;
        }

        $parts[] = \sprintf('%s, %s %s', $this->city, $this->state, $this->postalCode);
        $parts[] = $this->country;

        return implode("\n", $parts);
    }

    /**
     * Get the address as a single line.
     */
    public function getOneLineAddress(): string
    {
        $parts = [$this->addressLine1];

        if ($this->addressLine2) {
            $parts[] = $this->addressLine2;
        }

        $parts[] = $this->city;
        $parts[] = $this->state;
        $parts[] = $this->postalCode;
        $parts[] = $this->country;

        return implode(', ', $parts);
    }

    /**
     * Check if the location has geocoding coordinates.
     */
    public function hasCoordinates(): bool
    {
        return null !== $this->latitude && null !== $this->longitude;
    }
}
