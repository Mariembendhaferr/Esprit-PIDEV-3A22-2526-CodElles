<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ActiviteRepository;

#[ORM\Entity(repositoryClass: ActiviteRepository::class)]
#[ORM\Table(name: 'activite')]
class Activite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idActivite', type: 'integer')]
    
    private ?int $idActivite = null;


    public function getIdActivite(): ?int
    {
        return $this->idActivite;
    }

    public function setIdActivite(int $idActivite): self
    {
        $this->idActivite = $idActivite;
        return $this;
    }

    #[ORM\Column(name: 'nomActivite', type: 'string', nullable: false)]
    private ?string $nomActivite = null;

    public function getNomActivite(): ?string
    {
        return $this->nomActivite;
    }

    public function setNomActivite(string $nomActivite): self
    {
        $this->nomActivite = $nomActivite;
        return $this;
    }

    #[ORM\Column(name: 'descriptionActivite', type: 'string', nullable: true)]
    private ?string $descriptionActivite = null;

    public function getDescriptionActivite(): ?string
    {
        return $this->descriptionActivite;
    }

    public function setDescriptionActivite(?string $descriptionActivite): self
    {
        $this->descriptionActivite = $descriptionActivite;
        return $this;
    }

    #[ORM\Column(name: 'categorieActivite', type: 'string', nullable: false)]
    private ?string $categorieActivite = null;

    public function getCategorieActivite(): ?string
    {
        return $this->categorieActivite;
    }

    public function setCategorieActivite(string $categorieActivite): self
    {
        $this->categorieActivite = $categorieActivite;
        return $this;
    }

    #[ORM\Column(name: 'coutActivite', type: 'decimal', nullable: false)]
    private ?float $coutActivite = null;

    public function getCoutActivite(): ?float
    {
        return $this->coutActivite;
    }

    public function setCoutActivite(float $coutActivite): self
    {
        $this->coutActivite = $coutActivite;
        return $this;
    }

    #[ORM\Column(name: 'dureeActivite', type: 'integer', nullable: true)]
    private ?int $dureeActivite = null;

    public function getDureeActivite(): ?int
    {
        return $this->dureeActivite;
    }

    public function setDureeActivite(?int $dureeActivite): self
    {
        $this->dureeActivite = $dureeActivite;
        return $this;
    }

    #[ORM\Column(name: 'disponibiliteActivite', type: 'boolean', nullable: false)]
    private ?bool $disponibiliteActivite = null;

    public function isDisponibiliteActivite(): ?bool
    {
        return $this->disponibiliteActivite;
    }

    public function setDisponibiliteActivite(bool $disponibiliteActivite): self
    {
        $this->disponibiliteActivite = $disponibiliteActivite;
        return $this;
    }

    #[ORM\Column(name: 'localisationActivite', type: 'string', nullable: true)]
    private ?string $localisationActivite = null;

    public function getLocalisationActivite(): ?string
    {
        return $this->localisationActivite;
    }

    public function setLocalisationActivite(?string $localisationActivite): self
    {
        $this->localisationActivite = $localisationActivite;
        return $this;
    }

    #[ORM\Column(name: 'latitudeActivite', type: 'decimal', nullable: true)]
    private ?float $latitudeActivite = null;

    public function getLatitudeActivite(): ?float
    {
        return $this->latitudeActivite;
    }

    public function setLatitudeActivite(?float $latitudeActivite): self
    {
        $this->latitudeActivite = $latitudeActivite;
        return $this;
    }

    #[ORM\Column(name: 'longitudeActivite', type: 'decimal', nullable: true)]
    private ?float $longitudeActivite = null;

    public function getLongitudeActivite(): ?float
    {
        return $this->longitudeActivite;
    }

    public function setLongitudeActivite(?float $longitudeActivite): self
    {
        $this->longitudeActivite = $longitudeActivite;
        return $this;
    }

    #[ORM\Column(name: 'capaciteMaxActivite', type: 'integer', nullable: true)]
    private ?int $capaciteMaxActivite = null;

    public function getCapaciteMaxActivite(): ?int
    {
        return $this->capaciteMaxActivite;
    }

    public function setCapaciteMaxActivite(?int $capaciteMaxActivite): self
    {
        $this->capaciteMaxActivite = $capaciteMaxActivite;
        return $this;
    }

    #[ORM\Column(name: 'imageActivite', type: 'string', nullable: true)]
    private ?string $imageActivite = null;

    public function getImageActivite(): ?string
    {
        return $this->imageActivite;
    }

    public function setImageActivite(?string $imageActivite): self
    {
        $this->imageActivite = $imageActivite;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Fournisseuractivite::class, inversedBy: 'activites')]
    #[ORM\JoinColumn(name: 'idFournisseur', referencedColumnName: 'idFournisseur')]
    private ?Fournisseuractivite $fournisseuractivite = null;

    public function getFournisseuractivite(): ?Fournisseuractivite
    {
        return $this->fournisseuractivite;
    }

    public function setFournisseuractivite(?Fournisseuractivite $fournisseuractivite): self
    {
        $this->fournisseuractivite = $fournisseuractivite;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Avi::class, mappedBy: 'activite')]
    private Collection $avis;

    /**
     * @return Collection<int, Avi>
     */
    public function getAvis(): Collection
    {
        if (!$this->avis instanceof Collection) {
            $this->avis = new ArrayCollection();
        }
        return $this->avis;
    }

    public function addAvi(Avi $avi): self
    {
        if (!$this->getAvis()->contains($avi)) {
            $this->getAvis()->add($avi);
        }
        return $this;
    }

    public function removeAvi(Avi $avi): self
    {
        $this->getAvis()->removeElement($avi);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Planjournalier::class, mappedBy: 'activite')]
    private Collection $planjournaliers;

    public function __construct()
    {
        $this->avis = new ArrayCollection();
        $this->planjournaliers = new ArrayCollection();
    }

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

}
