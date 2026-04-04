<?php

namespace App\Entity;

use App\Repository\VoyageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VoyageRepository::class)]
class Voyage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $destination = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive]
    private ?int $duree = null; // in days

    #[ORM\Column(type: 'float')]
    #[Assert\Positive]
    private ?float $budgetEstime = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive]
    private ?int $nbPersonnes = null; // default number of persons for this voyage

    // Getters and Setters
    public function getId(): ?int { return $this->id; }
    public function getDestination(): ?string { return $this->destination; }
    public function setDestination(string $destination): static { $this->destination = $destination; return $this; }
    public function getDuree(): ?int { return $this->duree; }
    public function setDuree(int $duree): static { $this->duree = $duree; return $this; }
    public function getBudgetEstime(): ?float { return $this->budgetEstime; }
    public function setBudgetEstime(float $budgetEstime): static { $this->budgetEstime = $budgetEstime; return $this; }
    public function getNbPersonnes(): ?int { return $this->nbPersonnes; }
    public function setNbPersonnes(int $nbPersonnes): static { $this->nbPersonnes = $nbPersonnes; return $this; }
}