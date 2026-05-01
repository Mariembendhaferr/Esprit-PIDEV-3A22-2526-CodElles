<?php
// src/Entity/CommunityPost.php

namespace App\Entity;

use App\Repository\CommunityPostRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommunityPostRepository::class)]
#[ORM\Table(name: 'community_post')]
class CommunityPost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore-next-line */
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Le continent est obligatoire.')]
    #[Assert\Choice(
        choices: ['afrique', 'asie', 'europe', 'amerique', 'oceanie'],
        message: 'Continent invalide.'
    )]
    private string $continent;

    #[ORM\Column(type: 'string', length: 150)]
    #[Assert\NotBlank(message: 'La destination est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 150,
        minMessage: 'La destination doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La destination ne peut pas dépasser {{ limit }} caractères.',
    )]
    private string $destination;

    #[ORM\Column(type: 'string', length: 255)]
    ##[Assert\NotBlank(message: 'L\'URL de l\'image est obligatoire.')]
    private string $imageUrl;

    // 'pending' | 'approved' | 'rejected'
    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(
        choices: ['pending', 'approved', 'rejected'],
        message: 'Statut de modération invalide.'
    )]
    private string $moderationStatus = 'pending';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $moderationReason = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVisible = false;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $submittedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $approvedAt = null;

    public function __construct()
    {
        $this->submittedAt = new \DateTime();
    }

    // ── Getters / Setters ──────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getContinent(): string { return $this->continent; }
    public function setContinent(string $continent): self { $this->continent = $continent; return $this; }

    public function getDestination(): string { return $this->destination; }
    public function setDestination(string $destination): self { $this->destination = $destination; return $this; }

    public function getImageUrl(): string { return $this->imageUrl; }
    public function setImageUrl(string $imageUrl): self { $this->imageUrl = $imageUrl; return $this; }

    public function getModerationStatus(): string { return $this->moderationStatus; }
    public function setModerationStatus(string $status): self { $this->moderationStatus = $status; return $this; }

    public function getModerationReason(): ?string { return $this->moderationReason; }
    public function setModerationReason(?string $reason): self { $this->moderationReason = $reason; return $this; }

    public function isVisible(): bool { return $this->isVisible; }
    public function setIsVisible(bool $visible): self { $this->isVisible = $visible; return $this; }

    public function getSubmittedAt(): \DateTimeInterface { return $this->submittedAt; }

    public function getApprovedAt(): ?\DateTimeInterface { return $this->approvedAt; }
    public function setApprovedAt(?\DateTimeInterface $dt): self { $this->approvedAt = $dt; return $this; }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function approve(): self
    {
        $this->moderationStatus = 'approved';
        $this->isVisible        = true;
        $this->approvedAt       = new \DateTime();
        return $this;
    }

    public function reject(string $reason): self
    {
        $this->moderationStatus = 'rejected';
        $this->isVisible        = false;
        $this->moderationReason = $reason;
        return $this;
    }
}