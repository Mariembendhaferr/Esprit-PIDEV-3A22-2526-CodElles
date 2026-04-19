<?php

namespace App\Entity;

use App\Repository\VerificationLogsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VerificationLogsRepository::class)]
#[ORM\Table(name: 'verification_logs')]
class VerificationLogs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: false)]
    private User $user;

    #[ORM\Column(name: 'code_type', columnDefinition: "ENUM('email_verification','password_reset')")]
    private string $codeType;

    #[ORM\Column(name: 'code', type: 'string', length: 6)]
    private string $code;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'expires_at', type: 'datetime')]
    private \DateTimeInterface $expiresAt;

    #[ORM\Column(name: 'used', type: 'boolean', nullable: true)]
    private ?bool $used = false;

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $u): self { $this->user = $u; return $this; }
    public function getCodeType(): string { return $this->codeType; }
    public function setCodeType(string $c): self { $this->codeType = $c; return $this; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $c): self { $this->code = $c; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $c): self { $this->createdAt = $c; return $this; }
    public function getExpiresAt(): \DateTimeInterface { return $this->expiresAt; }
    public function setExpiresAt(\DateTimeInterface $e): self { $this->expiresAt = $e; return $this; }
    public function getUsed(): ?bool { return $this->used; }
    public function setUsed(?bool $u): self { $this->used = $u; return $this; }
}