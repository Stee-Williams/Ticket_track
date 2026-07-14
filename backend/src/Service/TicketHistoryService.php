<?php

namespace App\Service;

use App\Entity\Ticket;
use App\Entity\TicketHistory;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Enregistre les actions effectuées sur les tickets dans le journal d'audit.
 * Les entrées sont seulement persistées ; le flush est géré par l'appelant.
 */
class TicketHistoryService
{
    private const STATUS_LABELS = [
        Ticket::STATUS_WAITING     => 'En attente',
        Ticket::STATUS_IN_PROGRESS => 'En cours',
        Ticket::STATUS_DONE        => 'Traité',
    ];

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function log(Ticket $ticket, ?User $actor, string $action, ?string $details = null): void
    {
        $entry = (new TicketHistory())
            ->setTicket($ticket)
            ->setActor($actor)
            ->setAction($action)
            ->setDetails($details);

        $this->em->persist($entry);
    }

    public function logCreated(Ticket $ticket, User $actor): void
    {
        $this->log($ticket, $actor, TicketHistory::ACTION_CREATED, 'Ticket créé');
    }

    public function logAssigned(Ticket $ticket, ?User $actor, User $technician): void
    {
        $this->log($ticket, $actor, TicketHistory::ACTION_ASSIGNED, "Attribué à {$technician->getName()}");
    }

    public function logStatusChanged(Ticket $ticket, ?User $actor, string $from, string $to): void
    {
        $fromLabel = self::STATUS_LABELS[$from] ?? $from;
        $toLabel   = self::STATUS_LABELS[$to] ?? $to;
        $this->log($ticket, $actor, TicketHistory::ACTION_STATUS, "Statut : {$fromLabel} → {$toLabel}");
    }

    public function logCommented(Ticket $ticket, ?User $actor): void
    {
        $this->log($ticket, $actor, TicketHistory::ACTION_COMMENTED, 'Commentaire ajouté');
    }
}
