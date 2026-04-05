<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\VoyageRepository;

#[ORM\Entity(repositoryClass: VoyageRepository::class)]
#[ORM\Table(name: 'voyage')]
class Voyage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_voyage = null;

    public function getId_voyage(): ?int
    {
        return $this->id_voyage;
    }

    public function setId_voyage(int $id_voyage): self
    {
        $this->id_voyage = $id_voyage;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $titre = null;

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $destination = null;

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): self
    {
        $this->destination = $destination;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $continent = null;

    public function getContinent(): ?string
    {
        return $this->continent;
    }

    public function setContinent(string $continent): self
    {
        $this->continent = $continent;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $duree = null;

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): self
    {
        $this->duree = $duree;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $budget_estime = null;

    public function getBudget_estime(): ?float
    {
        return $this->budget_estime;
    }

    public function setBudget_estime(float $budget_estime): self
    {
        $this->budget_estime = $budget_estime;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $nb_personnes = null;

    public function getNb_personnes(): ?int
    {
        return $this->nb_personnes;
    }

    public function setNb_personnes(int $nb_personnes): self
    {
        $this->nb_personnes = $nb_personnes;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $image_url = null;

    public function getImage_url(): ?string
    {
        return $this->image_url;
    }

    public function setImage_url(?string $image_url): self
    {
        $this->image_url = $image_url;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Favori::class, mappedBy: 'voyage')]
    private Collection $favoris;

    /**
     * @return Collection<int, Favori>
     */
    public function getFavoris(): Collection
    {
        if (!$this->favoris instanceof Collection) {
            $this->favoris = new ArrayCollection();
        }
        return $this->favoris;
    }

    public function addFavori(Favori $favori): self
    {
        if (!$this->getFavoris()->contains($favori)) {
            $this->getFavoris()->add($favori);
        }
        return $this;
    }

    public function removeFavori(Favori $favori): self
    {
        $this->getFavoris()->removeElement($favori);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Planjournalier::class, mappedBy: 'voyage')]
    private Collection $planjournaliers;

    /**
     * @return Collection<int, Planjournalier>
     */
    public function getPlanjournaliers(): Collection
    {
        if (!$this->planjournaliers instanceof Collection) {
            $this->planjournaliers = new ArrayCollection();
        }
        return $this->planjournaliers;
    }

    public function addPlanjournalier(Planjournalier $planjournalier): self
    {
        if (!$this->getPlanjournaliers()->contains($planjournalier)) {
            $this->getPlanjournaliers()->add($planjournalier);
        }
        return $this;
    }

    public function removePlanjournalier(Planjournalier $planjournalier): self
    {
        $this->getPlanjournaliers()->removeElement($planjournalier);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'voyage')]
    private Collection $reservations;

    public function __construct()
    {
        $this->favoris = new ArrayCollection();
        $this->planjournaliers = new ArrayCollection();
        $this->reservations = new ArrayCollection();
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        if (!$this->reservations instanceof Collection) {
            $this->reservations = new ArrayCollection();
        }
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->getReservations()->contains($reservation)) {
            $this->getReservations()->add($reservation);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        $this->getReservations()->removeElement($reservation);
        return $this;
    }

    public function getIdVoyage(): ?int
    {
        return $this->id_voyage;
    }

    public function getBudgetEstime(): ?string
    {
        return $this->budget_estime;
    }

    public function setBudgetEstime(string $budget_estime): static
    {
        $this->budget_estime = $budget_estime;

        return $this;
    }

    public function getNbPersonnes(): ?int
    {
        return $this->nb_personnes;
    }

    public function setNbPersonnes(int $nb_personnes): static
    {
        $this->nb_personnes = $nb_personnes;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->image_url;
    }

    public function setImageUrl(?string $image_url): static
    {
        $this->image_url = $image_url;

        return $this;
    }

}
