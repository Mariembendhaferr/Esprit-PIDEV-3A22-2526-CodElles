<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\RatingRepository;

#[ORM\Entity(repositoryClass: RatingRepository::class)]
#[ORM\Table(name: 'rating')]
class Rating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_rating = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'ratingsReceived')]
    #[ORM\JoinColumn(name: 'rated_user_id', referencedColumnName: 'id_user', nullable: false)]
    private ?User $ratedUser = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'ratingsGiven')]
    #[ORM\JoinColumn(name: 'rater_user_id', referencedColumnName: 'id_user', nullable: false)]
    private ?User $raterUser = null;

    #[ORM\Column(type: 'integer')]
    private ?int $stars = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $created_at = null;

    public function getId_rating(): ?int { return $this->id_rating; }

    public function getRatedUser(): ?User { return $this->ratedUser; }
    public function setRatedUser(?User $ratedUser): self { $this->ratedUser = $ratedUser; return $this; }

    public function getRaterUser(): ?User { return $this->raterUser; }
    public function setRaterUser(?User $raterUser): self { $this->raterUser = $raterUser; return $this; }

    public function getStars(): ?int { return $this->stars; }
    public function setStars(int $stars): self { $this->stars = $stars; return $this; }

    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $comment): self { $this->comment = $comment; return $this; }

    public function getCreated_at(): ?\DateTimeInterface { return $this->created_at; }
    public function setCreated_at(\DateTimeInterface $created_at): self { $this->created_at = $created_at; return $this; }

    public function getIdRating(): ?int
    {
        return $this->id_rating;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
}