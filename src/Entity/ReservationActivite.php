<?php

namespace App\Entity;

use App\Repository\ReservationActiviteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationActiviteRepository::class)]
class ReservationActivite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reservationActivites')]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'reservationActivites')]
    #[ORM\JoinColumn(name: 'id_activite', referencedColumnName: 'idActivite', nullable: false)]
    private ?Activite $activite = null;
    

    #[ORM\Column]
    private ?\DateTime $dateActivite = null;

    #[ORM\Column]
    private ?int $nombreParticipants = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\Column]
    private ?\DateTime $dateReservation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $User): static
    {
        $this->user = $User;

        return $this;
    }

    public function getActivite(): ?Activite
    {
        return $this->activite;
    }

    public function setActivite(?Activite $activite): static
    {
        $this->activite = $activite;

        return $this;
    }

    public function getDateActivite(): ?\DateTime
    {
        return $this->dateActivite;
    }

    public function setDateActivite(\DateTime $dateActivite): static
    {
        $this->dateActivite = $dateActivite;

        return $this;
    }

    public function getNombreParticipants(): ?int
    {
        return $this->nombreParticipants;
    }

    public function setNombreParticipants(int $nombreParticipants): static
    {
        $this->nombreParticipants = $nombreParticipants;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateReservation(): ?\DateTime
    {
        return $this->dateReservation;
    }

    public function setDateReservation(\DateTime $dateReservation): static
    {
        $this->dateReservation = $dateReservation;

        return $this;
    }
}
