<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Ticket;
use App\Entity\TicketComment;
use App\Entity\TicketHistory;
use App\Entity\User;

/**
 * Transforme les entités Doctrine en tableaux au format exact attendu
 * par le frontend React (mêmes clés que l'ancienne API).
 */
class EntityNormalizer
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    public function user(User $u, bool $withCreatedAt = false): array
    {
        $data = [
            'id'    => $u->getId(),
            'name'  => $u->getName(),
            'email' => $u->getEmail(),
            'role'  => $u->getRole(),
            'phone' => $u->getPhone(),
        ];
        if ($withCreatedAt) {
            $data['created_at'] = $u->getCreatedAt()->format(self::DATE_FORMAT);
        }
        return $data;
    }

    public function ticket(Ticket $t): array
    {
        return [
            'id'              => $t->getId(),
            'reference'       => $t->getReference(),
            'title'           => $t->getTitle(),
            'description'     => $t->getDescription(),
            'category'        => $t->getCategory(),
            'priority'        => $t->getPriority(),
            'status'          => $t->getStatus(),
            'created_by'      => $t->getCreatedBy()->getId(),
            'assigned_to'     => $t->getAssignedTo()?->getId(),
            'creator_name'    => $t->getCreatedBy()->getName(),
            'creator_email'   => $t->getCreatedBy()->getEmail(),
            'technician_name' => $t->getAssignedTo()?->getName(),
            'created_at'      => $t->getCreatedAt()->format(self::DATE_FORMAT),
            'updated_at'      => $t->getUpdatedAt()->format(self::DATE_FORMAT),
            'resolved_at'     => $t->getResolvedAt()?->format(self::DATE_FORMAT),
        ];
    }

    public function ticketList(array $row): array
    {
        $createdAt = $row['created_at'];
        if (!$createdAt instanceof \DateTimeInterface && !is_string($createdAt)) {
            throw new \UnexpectedValueException('Date de création de ticket invalide.');
        }

        return [
            'id'              => (int) $row['id'],
            'reference'       => $row['reference'],
            'title'           => $row['title'],
            'category'        => $row['category'],
            'priority'        => $row['priority'],
            'status'          => $row['status'],
            'creator_name'    => $row['creator_name'],
            'technician_name' => $row['technician_name'],
            'created_at'      => $createdAt instanceof \DateTimeInterface
                ? $createdAt->format(self::DATE_FORMAT)
                : $createdAt,
        ];
    }

    public function comment(TicketComment $c): array
    {
        return [
            'id'          => $c->getId(),
            'message'     => $c->getMessage(),
            'created_at'  => $c->getCreatedAt()->format(self::DATE_FORMAT),
            'author'      => $c->getUser()->getName(),
            'author_role' => $c->getUser()->getRole(),
        ];
    }

    public function history(TicketHistory $h): array
    {
        return [
            'id'         => $h->getId(),
            'action'     => $h->getAction(),
            'details'    => $h->getDetails(),
            'actor'      => $h->getActor()?->getName(),
            'actor_role' => $h->getActor()?->getRole(),
            'created_at' => $h->getCreatedAt()->format(self::DATE_FORMAT),
        ];
    }

    public function notification(Notification $n): array
    {
        return [
            'id'         => $n->getId(),
            'ticket_id'  => $n->getTicket()?->getId(),
            'message'    => $n->getMessage(),
            'is_read'    => $n->isRead(),
            'created_at' => $n->getCreatedAt()->format(self::DATE_FORMAT),
        ];
    }
}
