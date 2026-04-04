<?php

namespace App\Entity;

use App\Repository\FournisseurActiviteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FournisseurActiviteRepository::class)]
#[ORM\Table(name: 'fournisseuractivite')]
class FournisseurActivite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idFournisseur')]
    private ?int $id = null;

    #[ORM\Column(name: 'nomFournisseur', length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    private ?string $nomFournisseur = null;

    #[ORM\Column(name: 'emailFournisseur', length: 255)]
    #[Assert\NotBlank(message: "L'email est obligatoire")]
    #[Assert\Email(message: "L'email n'est pas valide")]
    private ?string $emailFournisseur = null;

    #[ORM\Column(name: 'telephoneFournisseur')]
    #[Assert\NotBlank(message: 'Le téléphone est obligatoire')]
    private ?int $telephoneFournisseur = null;

    #[ORM\Column(name: 'adresseFournisseur', length: 255, nullable: true)]
    private ?string $adresseFournisseur = null;

    #[ORM\Column(name: 'specialiteFournisseur', length: 255, nullable: true)]
    private ?string $specialiteFournisseur = null;

    #[ORM\Column(name: 'websiteFournisseur', length: 500, nullable: true)]
    #[Assert\Url(message: "L'URL n'est pas valide")]
    private ?string $websiteFournisseur = null;

    #[ORM\ManyToMany(targetEntity: Activite::class, mappedBy: 'fournisseurs')]
    private Collection $activites;

    public function __construct() { $this->activites = new ArrayCollection(); }

    public function getId(): ?int { return $this->id; }

    public function getNomFournisseur(): ?string { return $this->nomFournisseur; }
    public function setNomFournisseur(string $v): static { $this->nomFournisseur = $v; return $this; }

    public function getEmailFournisseur(): ?string { return $this->emailFournisseur; }
    public function setEmailFournisseur(string $v): static { $this->emailFournisseur = $v; return $this; }

    public function getTelephoneFournisseur(): ?int { return $this->telephoneFournisseur; }
    public function setTelephoneFournisseur(int $v): static { $this->telephoneFournisseur = $v; return $this; }

    public function getAdresseFournisseur(): ?string { return $this->adresseFournisseur; }
    public function setAdresseFournisseur(?string $v): static { $this->adresseFournisseur = $v; return $this; }

    public function getSpecialiteFournisseur(): ?string { return $this->specialiteFournisseur; }
    public function setSpecialiteFournisseur(?string $v): static { $this->specialiteFournisseur = $v; return $this; }

    public function getWebsiteFournisseur(): ?string { return $this->websiteFournisseur; }
    public function setWebsiteFournisseur(?string $v): static { $this->websiteFournisseur = $v; return $this; }

    /** @return Collection<int, Activite> */
    public function getActivites(): Collection { return $this->activites; }

    public function __toString(): string { return $this->nomFournisseur ?? ''; }
}