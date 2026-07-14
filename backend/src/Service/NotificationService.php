<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Création des notifications (suivi en temps réel).
 */
class NotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
    ) {
    }

    public function notify(User $user, ?Ticket $ticket, string $message): void
    {
        $notif = (new Notification())
            ->setUser($user)
            ->setTicket($ticket)
            ->setMessage($message);
        $this->em->persist($notif);
    }

    public function notifyManagers(Ticket $ticket, string $message): void
    {
        foreach ($this->users->findBy(['role' => User::ROLE_MANAGER]) as $manager) {
            $this->notify($manager, $ticket, $message);
        }
    }
}
