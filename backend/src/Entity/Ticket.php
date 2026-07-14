<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ORM\Table(name: 'tickets')]
#[ORM\HasLifecycleCallbacks]
class Ticket
{
    public const STATUS_WAITING     = 'en_attente';
    public const STATUS_IN_PROGRESS = 'en_cours';
    public const STATUS_DONE        = 'traite';

    public const PRIORITIES = ['basse', 'normale', 'haute', 'urgente'];
    public const STATUSES   = ['en_attente', 'en_cours', 'traite'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private string $reference;

    #[ORM\Column(length: 200)]
    private string $title;

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    #[ORM\Column(length: 80)]
    private string $category = 'Général';

    #[ORM\Column(length: 20)]
    private string $priority = 'normale';

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_WAITING;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $createdBy;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'assigned_to', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $assignedTo = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(name: 'resolved_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $resolvedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getReference(): string { return $this->reference; }
    public function setReference(string $reference): self { $this->reference = $reference; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }

    public function getCategory(): string { return $this->category; }
    public function setCategory(string $category): self { $this->category = $category; return $this; }

    public function getPriority(): string { return $this->priority; }
    public function setPriority(string $priority): self { $this->priority = $priority; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getCreatedBy(): User { return $this->createdBy; }
    public function setCreatedBy(User $user): self { $this->createdBy = $user; return $this; }

    public function getAssignedTo(): ?User { return $this->assignedTo; }
    public function setAssignedTo(?User $user): self { $this->assignedTo = $user; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeInterface $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    public function getResolvedAt(): ?\DateTimeInterface { return $this->resolvedAt; }
    public function setResolvedAt(?\DateTimeInterface $resolvedAt): self { $this->resolvedAt = $resolvedAt; return $this; }
}
