<?php

namespace App\Entity;

use App\Repository\RatingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RatingRepository::class)]
#[ORM\Table(name: 'rating')]
class Rating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_rating', type: 'integer')]
    private ?int $idRating = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'rated_user_id', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private User $ratedUser;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'rater_user_id', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private User $raterUser;

    #[ORM\Column(name: 'stars', type: 'integer')]
    private int $stars;

    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTimeInterface $createdAt;

    // Optional: Add sentiment fields if you want to store AI analysis
    // Uncomment if you add these columns to your database
    /*
    #[ORM\Column(name: 'sentiment', type: 'string', length: 20, nullable: true)]
    private ?string $sentiment = null;

    #[ORM\Column(name: 'sentiment_emoji', type: 'string', length: 10, nullable: true)]
    private ?string $sentimentEmoji = null;
    */

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getIdRating(): ?int 
    { 
        return $this->idRating; 
    }
    
    public function getRatedUser(): User 
    { 
        return $this->ratedUser; 
    }
    
    public function setRatedUser(User $u): self 
    { 
        $this->ratedUser = $u; 
        return $this; 
    }
    
    public function getRaterUser(): User 
    { 
        return $this->raterUser; 
    }
    
    public function setRaterUser(User $u): self 
    { 
        $this->raterUser = $u; 
        return $this; 
    }
    
    public function getStars(): int 
    { 
        return $this->stars; 
    }
    
    public function setStars(int $s): self 
    { 
        $this->stars = $s; 
        return $this; 
    }
    
    public function getComment(): ?string 
    { 
        return $this->comment; 
    }
    
    public function setComment(?string $c): self 
    { 
        $this->comment = $c; 
        return $this; 
    }
    
    public function getCreatedAt(): \DateTimeInterface 
    { 
        return $this->createdAt; 
    }
    
    public function setCreatedAt(\DateTimeInterface $c): self 
    { 
        $this->createdAt = $c; 
        return $this; 
    }

}