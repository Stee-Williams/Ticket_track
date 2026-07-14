<?php

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\TicketHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TicketHistory>
 */
class TicketHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketHistory::class);
    }

    /**
     * @return TicketHistory[]
     */
    public function findForTicket(Ticket $ticket): array
    {
        return $this->createQueryBuilder('h')
            ->addSelect('actor')
            ->leftJoin('h.actor', 'actor')
            ->where('h.ticket = :ticket')
            ->setParameter('ticket', $ticket)
            ->orderBy('h.createdAt', 'ASC')
            ->addOrderBy('h.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
