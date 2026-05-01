<?php
// src/Entity/Favori.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\FavoriRepository;

#[ORM\Entity(repositoryClass: FavoriRepository::class)]
#[ORM\Table(name: 'favoris')]
#[ORM\UniqueConstraint(name: 'unique_favori', columns: ['id_utilisateur', 'id_voyage'])]
class Favori
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_favori = null;

    // Relation ManyToOne vers User
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id_user', nullable: false)]
    private ?User $user = null;

    // Relation ManyToOne vers Voyage
    #[ORM\ManyToOne(targetEntity: Voyage::class, inversedBy: 'favoris')]
    #[ORM\JoinColumn(name: 'id_voyage', referencedColumnName: 'id_voyage', nullable: false)]
    private ?Voyage $voyage = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_ajout = null;

    // Getters et Setters
    public function getId_favori(): ?int
    {
        return $this->id_favori;
    }

    public function setId_favori(int $id_favori): self
    {
        $this->id_favori = $id_favori;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getVoyage(): ?Voyage
    {
        return $this->voyage;
    }

    public function setVoyage(?Voyage $voyage): self
    {
        $this->voyage = $voyage;
        return $this;
    }

    public function getDateAjout(): ?\DateTimeInterface
    {
        return $this->date_ajout;
    }

    public function setDateAjout(\DateTimeInterface $date_ajout): self
    {
        $this->date_ajout = $date_ajout;
        return $this;
    }
}