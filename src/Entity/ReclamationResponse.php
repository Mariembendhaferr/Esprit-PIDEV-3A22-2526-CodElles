<?php

namespace App\Entity;

use App\Repository\ReclamationResponseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReclamationResponseRepository::class)]
#[ORM\Table(name: 'response')]
class ReclamationResponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_response')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Reclamation::class)]
    #[ORM\JoinColumn(name: 'id_reclamation', referencedColumnName: 'id_reclamation', nullable: false, onDelete: 'CASCADE')]
    private ?Reclamation $reclamation = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $contenu = '';

    #[ORM\Column(name: 'date_response', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateResponse = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_admin', referencedColumnName: 'id_user', nullable: false)]
    private ?User $admin = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReclamation(): ?Reclamation
    {
        return $this->reclamation;
    }

    public function setReclamation(?Reclamation $reclamation): static
    {
        $this->reclamation = $reclamation;

        return $this;
    }

    public function getContenu(): string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getDateResponse(): ?\DateTimeInterface
    {
        return $this->dateResponse;
    }

    public function setDateResponse(?\DateTimeInterface $dateResponse): static
    {
        $this->dateResponse = $dateResponse;

        return $this;
    }

    public function getAdmin(): ?User
    {
        return $this->admin;
    }

    public function setAdmin(?User $admin): static
    {
        $this->admin = $admin;

        return $this;
    }
}
