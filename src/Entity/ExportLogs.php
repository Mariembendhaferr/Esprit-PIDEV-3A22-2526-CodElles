<?php

namespace App\Entity;

use App\Repository\ExportLogsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExportLogsRepository::class)]
#[ORM\Table(name: 'export_logs')]
class ExportLogs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'export_type', type: 'string', length: 50)]
    private string $exportType;

    #[ORM\Column(name: 'file_path', type: 'string', length: 255)]
    private string $filePath;

    #[ORM\Column(name: 'created_by', type: 'integer', nullable: true)]
    private ?int $createdBy = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'record_count', type: 'integer', nullable: true)]
    private ?int $recordCount = 0;

    public function getId(): ?int { return $this->id; }
    public function getExportType(): string { return $this->exportType; }
    public function setExportType(string $e): self { $this->exportType = $e; return $this; }
    public function getFilePath(): string { return $this->filePath; }
    public function setFilePath(string $f): self { $this->filePath = $f; return $this; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function setCreatedBy(?int $u): self { $this->createdBy = $u; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $c): self { $this->createdAt = $c; return $this; }
    public function getRecordCount(): ?int { return $this->recordCount; }
    public function setRecordCount(?int $r): self { $this->recordCount = $r; return $this; }
}