<?php

declare(strict_types=1);

namespace App\Entity\Embeddable;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class Address
{
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Street address cannot be longer than {{ limit }} characters'
    )]
    #[Groups(['client:read', 'client:write'])]
    private ?string $street = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(
        max: 100,
        maxMessage: 'City cannot be longer than {{ limit }} characters'
    )]
    #[Groups(['client:read', 'client:write'])]
    private ?string $city = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(
        max: 100,
        maxMessage: 'State cannot be longer than {{ limit }} characters'
    )]
    #[Groups(['client:read', 'client:write'])]
    private ?string $state = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Length(
        max: 20,
        maxMessage: 'Postal code cannot be longer than {{ limit }} characters'
    )]
    #[Groups(['client:read', 'client:write'])]
    private ?string $postalCode = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Country cannot be longer than {{ limit }} characters'
    )]
    #[Groups(['client:read', 'client:write'])]
    private ?string $country = null;

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Check if address has any data.
     */
    public function isEmpty(): bool
    {
        return null === $this->street
            && null === $this->city
            && null === $this->state
            && null === $this->postalCode
            && null === $this->country;
    }

    /**
     * Get formatted address string.
     */
    public function getFormatted(): string
    {
        $parts = array_filter([
            $this->street,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country,
        ]);

        return implode(', ', $parts);
    }
}
