<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\FournisseuractiviteRepository;

#[ORM\Entity(repositoryClass: FournisseuractiviteRepository::class)]
#[ORM\Table(name: 'fournisseuractivite')]
class Fournisseuractivite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idFournisseur', type: 'integer')]
    private ?int $idFournisseur = null;

    public function getIdFournisseur(): ?int
    {
        return $this->idFournisseur;
    }

    public function setIdFournisseur(int $idFournisseur): self
    {
        $this->idFournisseur = $idFournisseur;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nomFournisseur = null;

    public function getNomFournisseur(): ?string
    {
        return $this->nomFournisseur;
    }

    public function setNomFournisseur(string $nomFournisseur): self
    {
        $this->nomFournisseur = $nomFournisseur;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $emailFournisseur = null;

    public function getEmailFournisseur(): ?string
    {
        return $this->emailFournisseur;
    }

    public function setEmailFournisseur(string $emailFournisseur): self
    {
        $this->emailFournisseur = $emailFournisseur;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $telephoneFournisseur = null;

    public function getTelephoneFournisseur(): ?string
    {
        return $this->telephoneFournisseur;
    }

    public function setTelephoneFournisseur(string $telephoneFournisseur): self
    {
        $this->telephoneFournisseur = $telephoneFournisseur;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $adresseFournisseur = null;

    public function getAdresseFournisseur(): ?string
    {
        return $this->adresseFournisseur;
    }

    public function setAdresseFournisseur(?string $adresseFournisseur): self
    {
        $this->adresseFournisseur = $adresseFournisseur;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $specialiteFournisseur = null;

    public function getSpecialiteFournisseur(): ?string
    {
        return $this->specialiteFournisseur;
    }

    public function setSpecialiteFournisseur(?string $specialiteFournisseur): self
    {
        $this->specialiteFournisseur = $specialiteFournisseur;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $websiteFournisseur = null;

    public function getWebsiteFournisseur(): ?string
    {
        return $this->websiteFournisseur;
    }

    public function setWebsiteFournisseur(?string $websiteFournisseur): self
    {
        $this->websiteFournisseur = $websiteFournisseur;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Activite::class, mappedBy: 'fournisseuractivite')]
    private Collection $activites;

    public function __construct()
    {
        $this->activites = new ArrayCollection();
    }

    /**
     * @return Collection<int, Activite>
     */
    public function getActivites(): Collection
    {
        if (!$this->activites instanceof Collection) {
            $this->activites = new ArrayCollection();
        }
        return $this->activites;
    }

    public function addActivite(Activite $activite): self
    {
        if (!$this->getActivites()->contains($activite)) {
            $this->getActivites()->add($activite);
        }
        return $this;
    }

    public function removeActivite(Activite $activite): self
    {
        $this->getActivites()->removeElement($activite);
        return $this;
    }

}
