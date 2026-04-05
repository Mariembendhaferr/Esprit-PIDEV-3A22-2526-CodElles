<?php

namespace App\Entity;

use App\Repository\ActiviteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActiviteRepository::class)]
#[ORM\Table(name: 'activite')]
class Activite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idActivite')]
    private ?int $id = null;

    #[ORM\Column(name: 'nomActivite', length: 255)]
    private string $nomActivite = '';

    #[ORM\Column(name: 'descriptionActivite', length: 255, nullable: true)]
    private ?string $descriptionActivite = null;

    #[ORM\Column(name: 'categorieActivite', length: 255)]
    private string $categorieActivite = '';

    #[ORM\Column(name: 'coutActivite', type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $coutActivite = '0.00';

    #[ORM\Column(name: 'dureeActivite', nullable: true)]
    private ?int $dureeActivite = null;

    #[ORM\Column(name: 'disponibiliteActivite', type: Types::BOOLEAN)]
    private bool $disponibiliteActivite = true;

    #[ORM\Column(name: 'localisationActivite', length: 255, nullable: true)]
    private ?string $localisationActivite = null;

    #[ORM\Column(name: 'capaciteMaxActivite', nullable: true)]
    private ?int $capaciteMaxActivite = null;

    #[ORM\Column(name: 'imageActivite', length: 255, nullable: true)]
    private ?string $imageActivite = null;

    #[ORM\ManyToOne(inversedBy: null)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', nullable: false)]
    private ?User $user = null;

    /** @var Collection<int, Avis> */
    #[ORM\OneToMany(targetEntity: Avis::class, mappedBy: 'activite')]
    private Collection $avis;

    public function __construct()
    {
        $this->avis = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomActivite(): string
    {
        return $this->nomActivite;
    }

    public function setNomActivite(string $nomActivite): static
    {
        $this->nomActivite = $nomActivite;

        return $this;
    }

    public function getDescriptionActivite(): ?string
    {
        return $this->descriptionActivite;
    }

    public function setDescriptionActivite(?string $descriptionActivite): static
    {
        $this->descriptionActivite = $descriptionActivite;

        return $this;
    }

    public function getCategorieActivite(): string
    {
        return $this->categorieActivite;
    }

    public function setCategorieActivite(string $categorieActivite): static
    {
        $this->categorieActivite = $categorieActivite;

        return $this;
    }

    public function getCoutActivite(): string
    {
        return $this->coutActivite;
    }

    public function setCoutActivite(string $coutActivite): static
    {
        $this->coutActivite = $coutActivite;

        return $this;
    }

    public function getDureeActivite(): ?int
    {
        return $this->dureeActivite;
    }

    public function setDureeActivite(?int $dureeActivite): static
    {
        $this->dureeActivite = $dureeActivite;

        return $this;
    }

    public function isDisponibiliteActivite(): bool
    {
        return $this->disponibiliteActivite;
    }

    public function setDisponibiliteActivite(bool $disponibiliteActivite): static
    {
        $this->disponibiliteActivite = $disponibiliteActivite;

        return $this;
    }

    public function getLocalisationActivite(): ?string
    {
        return $this->localisationActivite;
    }

    public function setLocalisationActivite(?string $localisationActivite): static
    {
        $this->localisationActivite = $localisationActivite;

        return $this;
    }

    public function getCapaciteMaxActivite(): ?int
    {
        return $this->capaciteMaxActivite;
    }

    public function setCapaciteMaxActivite(?int $capaciteMaxActivite): static
    {
        $this->capaciteMaxActivite = $capaciteMaxActivite;

        return $this;
    }

    public function getImageActivite(): ?string
    {
        return $this->imageActivite;
    }

    public function setImageActivite(?string $imageActivite): static
    {
        $this->imageActivite = $imageActivite;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /** @return Collection<int, Avis> */
    public function getAvis(): Collection
    {
        return $this->avis;
    }

    public function __toString(): string
    {
        return $this->nomActivite;
    }
}
