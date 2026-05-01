<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\PlanjournalierRepository;

#[ORM\Entity(repositoryClass: PlanjournalierRepository::class)]
#[ORM\Table(name: 'planjournalier')]
class Planjournalier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_plan = null;

    public function getId_plan(): ?int
    {
        return $this->id_plan;
    }

    public function setId_plan(int $id_plan): self
    {
        $this->id_plan = $id_plan;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Voyage::class, inversedBy: 'planjournaliers')]
    #[ORM\JoinColumn(name: 'id_voyage', referencedColumnName: 'id_voyage')]
    private ?Voyage $voyage = null;

    public function getVoyage(): ?Voyage
    {
        return $this->voyage;
    }

    public function setVoyage(?Voyage $voyage): self
    {
        $this->voyage = $voyage;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotBlank(message: 'Le numéro du jour est obligatoire.')]
    #[Assert\GreaterThan(value: 0, message: 'Le jour doit être supérieur à 0.')]
    private ?int $jour = null;

    public function getJour(): ?int
    {
        return $this->jour;
    }

    public function setJour(int $jour): self
    {
        $this->jour = $jour;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le titre du jour est obligatoire.')]
    #[Assert\Length(
        min: 25,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ0-9\s\-\'\!\?]+$/u',
        message: 'Le titre peut contenir des lettres, chiffres, espaces et ponctuation simple.'
    )]
    private ?string $titre_jour = null;

    public function getTitre_jour(): ?string
    {
        return $this->titre_jour;
    }

    public function setTitre_jour(string $titre_jour): self
    {
        $this->titre_jour = $titre_jour;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Activite::class, inversedBy: 'planjournaliers')]
    #[ORM\JoinColumn(name: 'id_activite', referencedColumnName: 'idActivite')]
    #[Assert\NotNull(message: 'Veuillez sélectionner une activité.')]
    private ?Activite $activite = null;

    public function getActivite(): ?Activite
    {
        return $this->activite;
    }

    public function setActivite(?Activite $activite): self
    {
        $this->activite = $activite;
        return $this;
    }

    public function getIdPlan(): ?int
    {
        return $this->id_plan;
    }

    public function getTitreJour(): ?string
    {
        return $this->titre_jour;
    }

    public function setTitreJour(string $titre_jour): static
    {
        $this->titre_jour = $titre_jour;

        return $this;
    }
}