<?php

namespace App\Entity;

use App\Repository\TicketHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Journal d'audit : trace horodatée des actions effectuées sur un ticket
 * (création, attribution, changement de statut, commentaire).
 */
#[ORM\Entity(repositoryClass: TicketHistoryRepository::class)]
#[ORM\Table(name: 'ticket_history')]
#[ORM\Index(name: 'idx_history_ticket', columns: ['ticket_id'])]
class TicketHistory
{
    public const ACTION_CREATED   = 'created';
    public const ACTION_ASSIGNED  = 'assigned';
    public const ACTION_STATUS    = 'status_changed';
    public const ACTION_COMMENTED = 'commented';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(name: 'ticket_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Ticket $ticket;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'actor_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $actor = null;

    #[ORM\Column(length: 30)]
    private string $action;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getTicket(): Ticket { return $this->ticket; }
    public function setTicket(Ticket $ticket): self { $this->ticket = $ticket; return $this; }

    public function getActor(): ?User { return $this->actor; }
    public function setActor(?User $actor): self { $this->actor = $actor; return $this; }

    public function getAction(): string { return $this->action; }
    public function setAction(string $action): self { $this->action = $action; return $this; }

    public function getDetails(): ?string { return $this->details; }
    public function setDetails(?string $details): self { $this->details = $details; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
