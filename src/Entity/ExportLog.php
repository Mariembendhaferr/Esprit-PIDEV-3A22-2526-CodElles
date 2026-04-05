<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ExportLogRepository;

#[ORM\Entity(repositoryClass: ExportLogRepository::class)]
#[ORM\Table(name: 'export_logs')]
class ExportLog
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $export_type = null;

    public function getExport_type(): ?string
    {
        return $this->export_type;
    }

    public function setExport_type(string $export_type): self
    {
        $this->export_type = $export_type;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $file_path = null;

    public function getFile_path(): ?string
    {
        return $this->file_path;
    }

    public function setFile_path(string $file_path): self
    {
        $this->file_path = $file_path;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'exportLogs')]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id_user')]
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

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $record_count = null;

    public function getRecord_count(): ?int
    {
        return $this->record_count;
    }

    public function setRecord_count(?int $record_count): self
    {
        $this->record_count = $record_count;
        return $this;
    }

    public function getExportType(): ?string
    {
        return $this->export_type;
    }

    public function setExportType(string $export_type): static
    {
        $this->export_type = $export_type;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->file_path;
    }

    public function setFilePath(string $file_path): static
    {
        $this->file_path = $file_path;

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

    public function getRecordCount(): ?int
    {
        return $this->record_count;
    }

    public function setRecordCount(?int $record_count): static
    {
        $this->record_count = $record_count;

        return $this;
    }

}
