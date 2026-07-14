<?php

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\TicketComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TicketComment>
 */
class TicketCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketComment::class);
    }

    /**
     * @return TicketComment[]
     */
    public function findForTicket(Ticket $ticket): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect('u')
            ->join('c.user', 'u')
            ->where('c.ticket = :ticket')
            ->setParameter('ticket', $ticket)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
