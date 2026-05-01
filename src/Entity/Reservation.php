<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_reservation')]  // ← nom colonne base pi
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_client', referencedColumnName: 'id_client', nullable: false)]  // ← FK base pi
    private ?Client $client = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_voyage', referencedColumnName: 'id', nullable: false)]
    private ?Voyage $voyage = null;

    #[ORM\Column(name: 'date_reservation', type: 'date')]
    private ?\DateTimeInterface $dateReservation = null;

    #[ORM\Column(name: 'date_depart', type: 'date')]
    #[Assert\NotBlank(message: 'La date de départ est obligatoire.')]
    #[Assert\Type('\DateTimeInterface')]
    #[Assert\GreaterThanOrEqual('today', message: 'La date de départ doit être aujourd\'hui ou dans le futur.')]
    private ?\DateTimeInterface $dateDepart = null;

    #[ORM\Column(name: 'date_retour', type: 'date')]
    #[Assert\NotBlank(message: 'La date de retour est obligatoire.')]
    #[Assert\Type('\DateTimeInterface')]
    #[Assert\GreaterThan(propertyPath: 'dateDepart', message: 'La date de retour doit être après la date de départ.')]
    private ?\DateTimeInterface $dateRetour = null;

    #[ORM\Column(name: 'nombre_personnes', type: 'integer')]
    #[Assert\NotBlank]
    #[Assert\Positive(message: 'Le nombre de personnes doit être au moins 1.')]
    #[Assert\LessThanOrEqual(value: 20, message: 'Maximum 20 personnes par réservation.')]
    private ?int $nombrePersonnes = null;

    #[ORM\Column(name: 'montant_total', type: 'decimal', precision: 10, scale: 2)]
    private ?float $montantTotal = null;

    #[ORM\Column(name: 'statut', length: 50)]
    private ?string $statut = 'en attente';

    #[ORM\Column(name: 'id_agent', nullable: true)]
    private ?int $idAgent = null;

    #[ORM\OneToMany(mappedBy: 'reservation', targetEntity: Paiement::class, cascade: ['persist', 'remove'])]
    private Collection $paiements;

    public function __construct()
    {
        $this->paiements = new ArrayCollection();
    }

    /**
     * Calcule le montant total basé sur le budgetEstime du voyage
     */
    public function calculerMontant(): void
    {
        if (!$this->voyage || !$this->dateDepart || !$this->dateRetour || !$this->nombrePersonnes) {
            return;
        }

        $days = (int) $this->dateDepart->diff($this->dateRetour)->days;
        if ($days <= 0) $days = 1;

        $dureevoyage = $this->voyage->getDuree() ?: 1;
        $budgetParPersonne = $this->voyage->getBudgetEstime() ?: 0;

        $prixParPersonneParJour = $budgetParPersonne / $dureevoyage;

        $this->montantTotal = round($prixParPersonneParJour * $days * $this->nombrePersonnes, 2);
    }

    // Getters and Setters
    public function getId(): ?int { return $this->id; }
    public function getClient(): ?Client { return $this->client; }
    public function setClient(?Client $client): static { $this->client = $client; return $this; }
    public function getVoyage(): ?Voyage { return $this->voyage; }
    public function setVoyage(?Voyage $voyage): static { $this->voyage = $voyage; return $this; }
    public function getDateReservation(): ?\DateTimeInterface { return $this->dateReservation; }
    public function setDateReservation(\DateTimeInterface $dateReservation): static { $this->dateReservation = $dateReservation; return $this; }
    public function getDateDepart(): ?\DateTimeInterface { return $this->dateDepart; }
    public function setDateDepart(\DateTimeInterface $dateDepart): static { $this->dateDepart = $dateDepart; return $this; }
    public function getDateRetour(): ?\DateTimeInterface { return $this->dateRetour; }
    public function setDateRetour(\DateTimeInterface $dateRetour): static { $this->dateRetour = $dateRetour; return $this; }
    public function getNombrePersonnes(): ?int { return $this->nombrePersonnes; }
    public function setNombrePersonnes(int $nombrePersonnes): static { $this->nombrePersonnes = $nombrePersonnes; return $this; }
    public function getMontantTotal(): ?float { return $this->montantTotal; }
    public function setMontantTotal(float $montantTotal): static { $this->montantTotal = $montantTotal; return $this; }
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
    public function getIdAgent(): ?int { return $this->idAgent; }
    public function setIdAgent(?int $idAgent): static { $this->idAgent = $idAgent; return $this; }

    public function getPaiements(): Collection { return $this->paiements; }

    public function addPaiement(Paiement $paiement): static
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements->add($paiement);
            $paiement->setReservation($this);
        }
        return $this;
    }

    public function removePaiement(Paiement $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            if ($paiement->getReservation() === $this) {
                $paiement->setReservation(null);
            }
        }
        return $this;
    }
}