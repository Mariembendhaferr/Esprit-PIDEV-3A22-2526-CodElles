<?php

namespace App\Entity;

use App\Repository\ActiviteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActiviteRepository::class)]
#[ORM\Table(name: 'activite')]
class Activite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idActivite')]
    private ?int $id = null;

    #[ORM\Column(name: 'nomActivite', length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(min: 3, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères')]
    #[Assert\Regex(pattern: '/^[^0-9]*$/', message: 'Le nom ne doit pas contenir de chiffres')]
    private ?string $nomActivite = null;

    #[ORM\Column(name: 'descriptionActivite', type: Types::TEXT, nullable: true)]
    private ?string $descriptionActivite = null;

    #[ORM\Column(name: 'categorieActivite', length: 50)]
    #[Assert\NotBlank(message: 'La catégorie est obligatoire')]
    #[Assert\Choice(
    choices: ['sports', 'culture', 'nature', 'aventure', 'gastronomie', 'bien-etre', 'plage', 'visite'],
    message: 'Catégorie invalide'
)]
    private ?string $categorieActivite = null;

    #[ORM\Column(name: 'coutActivite', type: Types::FLOAT)]
    #[Assert\NotBlank(message: 'Le coût est obligatoire')]
    #[Assert\Positive(message: 'Le coût doit être positif')]
    private ?float $coutActivite = null;

    #[ORM\Column(name: 'dureeActivite')]
    #[Assert\NotBlank(message: 'La durée est obligatoire')]
    #[Assert\Positive(message: 'La durée doit être positive')]
    private ?int $dureeActivite = null;

    #[ORM\Column(name: 'disponibiliteActivite')]
    private bool $disponibiliteActivite = true;

    #[ORM\Column(name: 'localisationActivite', length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'La destination est obligatoire')]
    private ?string $localisationActivite = null;

    #[ORM\Column(name: 'capaciteMaxActivite', nullable: true)]
    #[Assert\Positive(message: 'La capacité doit être positive')]
    private ?int $capaciteMaxActivite = null;

    #[ORM\Column(name: 'imageActivite', length: 500, nullable: true)]
    private ?string $imageActivite = null;

    #[ORM\Column(name: 'latitudeActivite', type: Types::FLOAT, nullable: true)]
    private ?float $latitudeActivite = null;

    #[ORM\Column(name: 'longitudeActivite', type: Types::FLOAT, nullable: true)]
    private ?float $longitudeActivite = null;

    #[ORM\Column(name: 'statutActivite', length: 50)]
    private string $statutActivite = 'en_attente';

    #[ORM\Column(name: 'assignationToken', length: 100, nullable: true)]
    private ?string $assignationToken = null;

    #[ORM\Column(name: 'places_reserves', type: 'integer', nullable: true, options: ['default' => 0])]
    private ?int $placesReserves = 0;   
    #[ORM\OneToMany(targetEntity: Planjournalier::class, mappedBy: 'activite')]
    private Collection $planjournaliers;

    #[ORM\ManyToMany(targetEntity: FournisseurActivite::class, inversedBy: 'activites')]
    #[ORM\JoinTable(
        name: 'activite_fournisseur',
        joinColumns: [new ORM\JoinColumn(name: 'idActivite', referencedColumnName: 'idActivite')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'idFournisseur', referencedColumnName: 'idFournisseur')]
    )]
    private Collection $fournisseurs;

    /**
     * @var Collection<int, ReservationActivite>
     */
    #[ORM\OneToMany(targetEntity: ReservationActivite::class, mappedBy: 'activite')]
    private Collection $reservationActivites;

    public function __construct()
    {
        $this->fournisseurs = new ArrayCollection();
        $this->reservationActivites = new ArrayCollection();
        $this->planjournaliers = new ArrayCollection();

    }

    public function getId(): ?int { return $this->id; }

    public function getNomActivite(): ?string { return $this->nomActivite; }
    public function setNomActivite(string $v): static { $this->nomActivite = $v; return $this; }

    public function getDescriptionActivite(): ?string { return $this->descriptionActivite; }
    public function setDescriptionActivite(?string $v): static { $this->descriptionActivite = $v; return $this; }

    public function getCategorieActivite(): ?string { return $this->categorieActivite; }
    public function setCategorieActivite(string $v): static { $this->categorieActivite = $v; return $this; }

    public function getCoutActivite(): ?float { return $this->coutActivite; }
    public function setCoutActivite(float $v): static { $this->coutActivite = $v; return $this; }

    public function getDureeActivite(): ?int { return $this->dureeActivite; }
    public function setDureeActivite(int $v): static { $this->dureeActivite = $v; return $this; }

    public function isDisponibiliteActivite(): bool { return $this->disponibiliteActivite; }
    public function setDisponibiliteActivite(bool $v): static { $this->disponibiliteActivite = $v; return $this; }

    public function getLocalisationActivite(): ?string { return $this->localisationActivite; }
    public function setLocalisationActivite(?string $v): static { $this->localisationActivite = $v; return $this; }

    public function getCapaciteMaxActivite(): ?int { return $this->capaciteMaxActivite; }
    public function setCapaciteMaxActivite(?int $v): static { $this->capaciteMaxActivite = $v; return $this; }

    public function getImageActivite(): ?string { return $this->imageActivite; }
    public function setImageActivite(?string $v): static { $this->imageActivite = $v; return $this; }

    public function getLatitudeActivite(): ?float { return $this->latitudeActivite; }
    public function setLatitudeActivite(?float $v): static { $this->latitudeActivite = $v; return $this; }

    public function getLongitudeActivite(): ?float { return $this->longitudeActivite; }
    public function setLongitudeActivite(?float $v): static { $this->longitudeActivite = $v; return $this; }

    public function getStatutActivite(): string { return $this->statutActivite; }
    public function setStatutActivite(string $v): static { $this->statutActivite = $v; return $this; }

    public function getAssignationToken(): ?string { return $this->assignationToken; }
    public function setAssignationToken(?string $v): static { $this->assignationToken = $v; return $this; }

    public function getPlacesReserves(): ?int { return $this->placesReserves; }
    public function setPlacesReserves(?int $placesReserves): self { $this->placesReserves = $placesReserves; return $this; }       

    /** @return Collection<int, FournisseurActivite> */
    public function getFournisseurs(): Collection { return $this->fournisseurs; }

    public function addFournisseur(FournisseurActivite $f): static
    {
        if (!$this->fournisseurs->contains($f)) {
            $this->fournisseurs->add($f);
        }
        return $this;
    }

    public function removeFournisseur(FournisseurActivite $f): static
    {
        $this->fournisseurs->removeElement($f);
        return $this;
    }

    public function getNomsFournisseurs(): string
    {
        if ($this->fournisseurs->isEmpty()) return 'Non assigné';
        return implode(', ', $this->fournisseurs->map(
            fn(FournisseurActivite $f) => $f->getNomFournisseur()
        )->toArray());
    }

    public function __toString(): string { return $this->nomActivite ?? ''; }

    /**
     * @return Collection<int, ReservationActivite>
     */
    public function getReservationActivites(): Collection
    {
        return $this->reservationActivites;
    }

    public function addReservationActivite(ReservationActivite $reservationActivite): static
    {
        if (!$this->reservationActivites->contains($reservationActivite)) {
            $this->reservationActivites->add($reservationActivite);
            $reservationActivite->setActivite($this);
        }

        return $this;
    }

    public function removeReservationActivite(ReservationActivite $reservationActivite): static
    {
        if ($this->reservationActivites->removeElement($reservationActivite)) {
            // set the owning side to null (unless already changed)
            if ($reservationActivite->getActivite() === $this) {
                $reservationActivite->setActivite(null);
            }
        }

        return $this;
    }


 public function getPlacesRestantes(): int
{
    return $this->capaciteMaxActivite - ($this->placesReserves ?? 0);
}

    public function isDisponible(int $nbPlacesDemandees): bool
    {
        return $this->getPlacesRestantes() >= $nbPlacesDemandees;
    }
    /** @return Collection<int, Planjournalier> */
public function getPlanjournaliers(): Collection
{
    return $this->planjournaliers;
}

public function addPlanjournalier(Planjournalier $p): static
{
    if (!$this->planjournaliers->contains($p)) {
        $this->planjournaliers->add($p);
        $p->setActivite($this);
    }
    return $this;
}

public function removePlanjournalier(Planjournalier $p): static
{
    if ($this->planjournaliers->removeElement($p)) {
        if ($p->getActivite() === $this) {
            $p->setActivite(null);
        }
    }
    return $this;
}

}