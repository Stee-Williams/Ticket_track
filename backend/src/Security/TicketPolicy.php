<?php

namespace App\Security;

use App\Entity\Ticket;
use App\Entity\User;
use App\Exception\ApiException;

/**
 * Point unique de vérité pour les droits sur les tickets.
 *
 * Règles métier :
 *  - Utilisateur   : crée des tickets, ne voit/agit que sur les siens (créés par lui).
 *  - Technicien    : voit et change le statut des tickets qui lui sont attribués.
 *  - Gestionnaire  : tout gérer (voir, attribuer, supprimer).
 *
 * Les méthodes can* renvoient un booléen (utile pour l'affichage / le filtrage),
 * les méthodes assert* lèvent une ApiException 403 quand l'action est interdite.
 */
class TicketPolicy
{
    public function canView(User $user, Ticket $ticket): bool
    {
        if ($user->isManager()) {
            return true;
        }
        if ($user->isUser()) {
            return $ticket->getCreatedBy()->hasId($user->getId());
        }
        if ($user->isTechnician()) {
            return $ticket->getAssignedTo()?->hasId($user->getId()) ?? false;
        }
        return false;
    }

    public function canComment(User $user, Ticket $ticket): bool
    {
        return $this->canView($user, $ticket);
    }

    public function canChangeStatus(User $user, Ticket $ticket): bool
    {
        if ($user->isManager()) {
            return true;
        }
        return $user->isTechnician() && ($ticket->getAssignedTo()?->hasId($user->getId()) ?? false);
    }

    public function canAssign(User $user): bool
    {
        return $user->isManager();
    }

    public function canDelete(User $user, Ticket $ticket): bool
    {
        if ($user->isManager()) {
            return true;
        }

        // Le créateur peut supprimer son ticket tant qu'il n'a pas été pris en charge.
        return $ticket->getCreatedBy()->hasId($user->getId())
            && $ticket->getStatus() === Ticket::STATUS_WAITING;
    }

    public function assertCanView(User $user, Ticket $ticket): void
    {
        if (!$this->canView($user, $ticket)) {
            throw new ApiException('Accès refusé à ce ticket.', 403);
        }
    }

    public function assertCanComment(User $user, Ticket $ticket): void
    {
        if (!$this->canComment($user, $ticket)) {
            throw new ApiException('Accès refusé à ce ticket.', 403);
        }
    }

    public function assertCanChangeStatus(User $user, Ticket $ticket): void
    {
        if (!$this->canChangeStatus($user, $ticket)) {
            throw new ApiException('Vous n\'êtes pas autorisé à modifier ce ticket.', 403);
        }
    }

    public function assertCanAssign(User $user): void
    {
        if (!$this->canAssign($user)) {
            throw new ApiException('Accès refusé : privilèges insuffisants.', 403);
        }
    }

    public function assertCanDelete(User $user, Ticket $ticket): void
    {
        if (!$this->canDelete($user, $ticket)) {
            throw new ApiException('Suppression non autorisée.', 403);
        }
    }
}
