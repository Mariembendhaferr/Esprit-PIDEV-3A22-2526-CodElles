<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Voyage $voyage = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateReservation = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'La date de départ est obligatoire.')]
    #[Assert\Type('\DateTimeInterface')]
    #[Assert\GreaterThanOrEqual('today', message: 'La date de départ doit être aujourd\'hui ou dans le futur.')]
    private ?\DateTimeInterface $dateDepart = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'La date de retour est obligatoire.')]
    #[Assert\Type('\DateTimeInterface')]
    #[Assert\GreaterThan(propertyPath: 'dateDepart', message: 'La date de retour doit être après la date de départ.')]
    private ?\DateTimeInterface $dateRetour = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank]
    #[Assert\Positive(message: 'Le nombre de personnes doit être au moins 1.')]
    #[Assert\LessThanOrEqual(value: 20, message: 'Maximum 20 personnes par réservation.')]
    private ?int $nombrePersonnes = null;

    #[ORM\Column(type: 'float')]
    private ?float $montantTotal = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = 'en attente';

    #[ORM\Column(nullable: true)]
    private ?int $idAgent = null;

    /**
     * Calcule le montant total basé sur le budgetEstime du voyage
     * budgetEstime = prix par personne pour la durée du voyage
     * On ajuste proportionnellement si les dates diffèrent de la durée standard du voyage
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

        // Prix par personne par jour = budgetEstime / durée du voyage
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
}