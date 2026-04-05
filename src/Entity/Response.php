<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ResponseRepository;

#[ORM\Entity(repositoryClass: ResponseRepository::class)]
#[ORM\Table(name: 'response')]
class Response
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_response = null;

    public function getId_response(): ?int
    {
        return $this->id_response;
    }

    public function setId_response(int $id_response): self
    {
        $this->id_response = $id_response;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Reclamation::class, inversedBy: 'responses')]
    #[ORM\JoinColumn(name: 'id_reclamation', referencedColumnName: 'id_reclamation')]
    private ?Reclamation $reclamation = null;

    public function getReclamation(): ?Reclamation
    {
        return $this->reclamation;
    }

    public function setReclamation(?Reclamation $reclamation): self
    {
        $this->reclamation = $reclamation;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $contenu = null;

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): self
    {
        $this->contenu = $contenu;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_response = null;

    public function getDate_response(): ?\DateTimeInterface
    {
        return $this->date_response;
    }

    public function setDate_response(\DateTimeInterface $date_response): self
    {
        $this->date_response = $date_response;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'responses')]
    #[ORM\JoinColumn(name: 'id_admin', referencedColumnName: 'id_user')]
    private ?User $user = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getIdResponse(): ?int
    {
        return $this->id_response;
    }

    public function getDateResponse(): ?\DateTime
    {
        return $this->date_response;
    }

    public function setDateResponse(\DateTime $date_response): static
    {
        $this->date_response = $date_response;

        return $this;
    }

}
