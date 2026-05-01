<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\VerificationLogRepository;

#[ORM\Entity(repositoryClass: VerificationLogRepository::class)]
#[ORM\Table(name: 'verification_logs')]
class VerificationLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'verificationLogs')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user')]
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $code_type = null;

    public function getCode_type(): ?string
    {
        return $this->code_type;
    }

    public function setCode_type(string $code_type): self
    {
        $this->code_type = $code_type;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $code = null;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(?\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $expires_at = null;

    public function getExpires_at(): ?\DateTimeInterface
    {
        return $this->expires_at;
    }

    public function setExpires_at(\DateTimeInterface $expires_at): self
    {
        $this->expires_at = $expires_at;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $used = null;

    public function isUsed(): ?bool
    {
        return $this->used;
    }

    public function setUsed(?bool $used): self
    {
        $this->used = $used;
        return $this;
    }

    public function getCodeType(): ?string
    {
        return $this->code_type;
    }

    public function setCodeType(string $code_type): static
    {
        $this->code_type = $code_type;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getExpiresAt(): ?\DateTime
    {
        return $this->expires_at;
    }

    public function setExpiresAt(\DateTime $expires_at): static
    {
        $this->expires_at = $expires_at;

        return $this;
    }

}
