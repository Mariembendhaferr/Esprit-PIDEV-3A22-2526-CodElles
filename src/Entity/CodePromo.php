<?php

namespace App\Entity;

use App\Repository\CodePromoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CodePromoRepository::class)]
#[ORM\Table(name: 'code_promo')]
class CodePromo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32, unique: true)]
    private string $code;

    #[ORM\Column]
    private int $discount;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private bool $isUsed = false;

    // ✅ CORRECTION 1 : targetEntity + type
    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    // ✅ CORRECTION 2 : targetEntity + type
    #[ORM\ManyToOne(targetEntity: Reservation::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Reservation $reservation = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isUniversal = false;

    #[ORM\Column(options: ['default' => 1])]
    private int $maxUses = 1;

    #[ORM\Column(options: ['default' => 0])]
    private int $currentUses = 0;

    // Getters et Setters
    public function getId(): ?int { return $this->id; }

    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }

    public function getDiscount(): int { return $this->discount; }
    public function setDiscount(int $discount): static { $this->discount = $discount; return $this; }

    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function setExpiresAt(\DateTimeImmutable $expiresAt): static { $this->expiresAt = $expiresAt; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function isIsUsed(): bool { return $this->isUsed; }
    public function setIsUsed(bool $isUsed): static { $this->isUsed = $isUsed; return $this; }

    public function getClient(): ?Client { return $this->client; }
    public function setClient(?Client $client): static { $this->client = $client; return $this; }

    public function getReservation(): ?Reservation { return $this->reservation; }
    public function setReservation(?Reservation $reservation): static { $this->reservation = $reservation; return $this; }

    public function isIsUniversal(): bool { return $this->isUniversal; }
    public function setIsUniversal(bool $isUniversal): static { $this->isUniversal = $isUniversal; return $this; }

    public function getMaxUses(): int { return $this->maxUses; }
    public function setMaxUses(int $maxUses): static { $this->maxUses = $maxUses; return $this; }

    public function getCurrentUses(): int { return $this->currentUses; }
    public function setCurrentUses(int $currentUses): static { $this->currentUses = $currentUses; return $this; }
    
    public function incrementUses(): static { 
        $this->currentUses++; 
        if ($this->currentUses >= $this->maxUses) {
            $this->isUsed = true;
        }
        return $this; 
    }
}