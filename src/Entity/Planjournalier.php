<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

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
