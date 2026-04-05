<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\PaiementRepository;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
#[ORM\Table(name: 'paiement')]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_paiement = null;

    public function getId_paiement(): ?int
    {
        return $this->id_paiement;
    }

    public function setId_paiement(int $id_paiement): self
    {
        $this->id_paiement = $id_paiement;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Reservation::class, inversedBy: 'paiements')]
    #[ORM\JoinColumn(name: 'id_reservation', referencedColumnName: 'id_reservation')]
    private ?Reservation $reservation = null;

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): self
    {
        $this->reservation = $reservation;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $montant = null;

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): self
    {
        $this->montant = $montant;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_paiement = null;

    public function getDate_paiement(): ?\DateTimeInterface
    {
        return $this->date_paiement;
    }

    public function setDate_paiement(\DateTimeInterface $date_paiement): self
    {
        $this->date_paiement = $date_paiement;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $mode_paiement = null;

    public function getMode_paiement(): ?string
    {
        return $this->mode_paiement;
    }

    public function setMode_paiement(string $mode_paiement): self
    {
        $this->mode_paiement = $mode_paiement;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $reference_paiement = null;

    public function getReference_paiement(): ?string
    {
        return $this->reference_paiement;
    }

    public function setReference_paiement(string $reference_paiement): self
    {
        $this->reference_paiement = $reference_paiement;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $statut = null;

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $type_paiement = null;

    public function getType_paiement(): ?string
    {
        return $this->type_paiement;
    }

    public function setType_paiement(string $type_paiement): self
    {
        $this->type_paiement = $type_paiement;
        return $this;
    }

    public function getIdPaiement(): ?int
    {
        return $this->id_paiement;
    }

    public function getDatePaiement(): ?\DateTime
    {
        return $this->date_paiement;
    }

    public function setDatePaiement(\DateTime $date_paiement): static
    {
        $this->date_paiement = $date_paiement;

        return $this;
    }

    public function getModePaiement(): ?string
    {
        return $this->mode_paiement;
    }

    public function setModePaiement(string $mode_paiement): static
    {
        $this->mode_paiement = $mode_paiement;

        return $this;
    }

    public function getReferencePaiement(): ?string
    {
        return $this->reference_paiement;
    }

    public function setReferencePaiement(string $reference_paiement): static
    {
        $this->reference_paiement = $reference_paiement;

        return $this;
    }

    public function getTypePaiement(): ?string
    {
        return $this->type_paiement;
    }

    public function setTypePaiement(string $type_paiement): static
    {
        $this->type_paiement = $type_paiement;

        return $this;
    }

}
