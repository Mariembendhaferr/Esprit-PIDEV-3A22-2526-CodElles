<?php

namespace App\Entity;

use App\Repository\PaiementRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
#[ORM\Table(name: 'paiement')]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_paiement')]  // ← nom colonne base pi
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'paiements')]
    #[ORM\JoinColumn(name: 'id_reservation', referencedColumnName: 'id_reservation', nullable: false)]  // ← FK base pi
    private ?Reservation $reservation = null;

    #[ORM\Column(name: 'montant', type: 'decimal', precision: 10, scale: 2)]
    #[Assert\Positive]
    private ?float $montant = null;

    #[ORM\Column(name: 'date_paiement', type: 'datetime')]
    private ?\DateTimeInterface $datePaiement = null;

    #[ORM\Column(name: 'mode_paiement', length: 100)]
    private ?string $modePaiement = null;

    #[ORM\Column(name: 'reference_paiement', length: 255, unique: true)]
    private ?string $referencePaiement = null;

    #[ORM\Column(name: 'statut', length: 50)]
    private ?string $statut = 'en attente';

    #[ORM\Column(name: 'type_paiement', length: 100)]
    private ?string $typePaiement = 'Complet';

    // Getters and Setters
    public function getId(): ?int { return $this->id; }
    public function getReservation(): ?Reservation { return $this->reservation; }
    public function setReservation(?Reservation $reservation): static { $this->reservation = $reservation; return $this; }
    public function getMontant(): ?float { return $this->montant; }
    public function setMontant(float $montant): static { $this->montant = $montant; return $this; }
    public function getDatePaiement(): ?\DateTimeInterface { return $this->datePaiement; }
    public function setDatePaiement(\DateTimeInterface $datePaiement): static { $this->datePaiement = $datePaiement; return $this; }
    public function getModePaiement(): ?string { return $this->modePaiement; }
    public function setModePaiement(string $modePaiement): static { $this->modePaiement = $modePaiement; return $this; }
    public function getReferencePaiement(): ?string { return $this->referencePaiement; }
    public function setReferencePaiement(string $referencePaiement): static { $this->referencePaiement = $referencePaiement; return $this; }
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
    public function getTypePaiement(): ?string { return $this->typePaiement; }
    public function setTypePaiement(string $typePaiement): static { $this->typePaiement = $typePaiement; return $this; }
}