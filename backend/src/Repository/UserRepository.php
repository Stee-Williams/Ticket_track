<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => strtolower(trim($email))]);
    }

    /**
     * Liste des techniciens avec le nombre de tickets actifs (non traités).
     */
    public function findTechniciansWithLoad(): array
    {
        $rows = $this->createQueryBuilder('u')
            ->select('u.id, u.name, u.email, u.phone')
            ->addSelect('SUM(CASE WHEN t.status != :done THEN 1 ELSE 0 END) AS active_tickets')
            ->leftJoin('App\Entity\Ticket', 't', 'WITH', 't.assignedTo = u.id')
            ->where('u.role = :role')
            ->setParameter('role', User::ROLE_TECHNICIAN)
            ->setParameter('done', 'traite')
            ->groupBy('u.id, u.name, u.email, u.phone')
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(static function (array $r): array {
            return [
                'id'             => (int) $r['id'],
                'name'           => $r['name'],
                'email'          => $r['email'],
                'phone'          => $r['phone'],
                'active_tickets' => (int) $r['active_tickets'],
            ];
        }, $rows);
    }
}
