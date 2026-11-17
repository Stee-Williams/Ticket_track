<?php

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    /**
     * Liste paginée des tickets filtrée selon le rôle et les critères.
     *
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function paginateForUser(User $user, array $filters, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        $items = $this->filteredListQuery($user, $filters)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $this->countForUser($user, $filters)];
    }

    /**
     * Charge au plus le nombre demandé de tickets pour borner les exports.
     *
     * @return Ticket[]
     */
    public function findForExport(User $user, array $filters, int $limit): array
    {
        return $this->filteredQuery($user, $filters)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countForUser(User $user, array $filters): int
    {
        $qb = $this->createQueryBuilder('t')->select('COUNT(t.id)');
        $this->applyScope($qb, $user);
        $this->applyFilters($qb, $filters);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Construit la requête filtrée + triée (avec jointures pour éviter le N+1).
     */
    private function filteredQuery(User $user, array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')
            ->addSelect('creator', 'tech')
            ->join('t.createdBy', 'creator')
            ->leftJoin('t.assignedTo', 'tech');

        $this->applyScope($qb, $user);
        $this->applyFilters($qb, $filters);

        $this->applyOrdering($qb);

        return $qb;
    }

    /**
     * Select only the fields displayed in the paginated ticket list.
     */
    private function filteredListQuery(User $user, array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')
            ->select(
                't.id AS id',
                't.reference AS reference',
                't.title AS title',
                't.category AS category',
                't.priority AS priority',
                't.status AS status',
                'creator.name AS creator_name',
                'tech.name AS technician_name',
                't.createdAt AS created_at'
            )
            ->join('t.createdBy', 'creator')
            ->leftJoin('t.assignedTo', 'tech');

        $this->applyScope($qb, $user);
        $this->applyFilters($qb, $filters);
        $this->applyOrdering($qb);

        return $qb;
    }

    private function applyOrdering(QueryBuilder $qb): void
    {
        // Tri : statut (attente d'abord), puis priorité (urgente d'abord), puis date
        $qb->addSelect(
            "CASE t.status WHEN 'en_attente' THEN 0 WHEN 'en_cours' THEN 1 ELSE 2 END AS HIDDEN status_order"
        );
        $qb->addSelect(
            "CASE t.priority WHEN 'urgente' THEN 0 WHEN 'haute' THEN 1 WHEN 'normale' THEN 2 ELSE 3 END AS HIDDEN prio_order"
        );
        $qb->orderBy('status_order', 'ASC')
           ->addOrderBy('prio_order', 'ASC')
           ->addOrderBy('t.createdAt', 'DESC');
    }

    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['status']) && in_array($filters['status'], Ticket::STATUSES, true)) {
            $qb->andWhere('t.status = :status')->setParameter('status', $filters['status']);
        }
        if (!empty($filters['priority']) && in_array($filters['priority'], Ticket::PRIORITIES, true)) {
            $qb->andWhere('t.priority = :priority')->setParameter('priority', $filters['priority']);
        }
        if (!empty($filters['category'])) {
            $qb->andWhere('LOWER(t.category) = :cat')->setParameter('cat', strtolower(trim($filters['category'])));
        }
        if (!empty($filters['search'])) {
            $qb->andWhere('(LOWER(t.title) LIKE :s OR LOWER(t.description) LIKE :s OR LOWER(t.reference) LIKE :s)')
               ->setParameter('s', '%' . strtolower($filters['search']) . '%');
        }
    }

    public function generateReference(): string
    {
        return 'TCK-' . strtoupper(bin2hex(random_bytes(8)));
    }

    /**
     * Comptage des tickets groupés par un champ (status / priority / category),
     * avec application du périmètre selon le rôle.
     */
    public function countByField(string $field, ?User $scopeUser): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select("t.$field AS label, COUNT(t.id) AS total")
            ->groupBy("t.$field");

        $this->applyScope($qb, $scopeUser);

        $result = [];
        foreach ($qb->getQuery()->getResult() as $row) {
            $result[$row['label']] = (int) $row['total'];
        }
        return $result;
    }

    /**
     * Temps moyen de résolution en heures (tickets traités).
     */
    public function averageResolutionHours(?User $scopeUser): ?float
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT AVG(EXTRACT(EPOCH FROM (resolved_at - created_at)) / 3600) AS avg_hours
                FROM tickets
                WHERE status = 'traite' AND resolved_at IS NOT NULL";
        $params = [];
        if ($scopeUser) {
            [$clause, $params] = $this->scopeSql($scopeUser);
            $sql .= ' AND ' . $clause;
        }
        $value = $conn->executeQuery($sql, $params)->fetchOne();
        return $value !== null ? round((float) $value, 1) : null;
    }

    /**
     * Nombre de tickets créés par jour sur les 7 derniers jours.
     */
    public function creationTrend(?User $scopeUser): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT created_at::date AS day, COUNT(*) AS total
                FROM tickets
                WHERE created_at >= CURRENT_DATE - INTERVAL '6 days'";
        $params = [];
        if ($scopeUser) {
            [$clause, $params] = $this->scopeSql($scopeUser);
            $sql .= ' AND ' . $clause;
        }
        $sql .= ' GROUP BY created_at::date ORDER BY day ASC';

        return array_map(static function (array $r): array {
            return ['day' => $r['day'], 'total' => (int) $r['total']];
        }, $conn->executeQuery($sql, $params)->fetchAllAssociative());
    }

    private function applyScope($qb, ?User $user): void
    {
        if (!$user) {
            return;
        }
        if ($user->isUser()) {
            $qb->andWhere('IDENTITY(t.createdBy) = :uid')->setParameter('uid', $user->getId());
        } elseif ($user->isTechnician()) {
            $qb->andWhere('IDENTITY(t.assignedTo) = :uid')->setParameter('uid', $user->getId());
        }
    }

    /**
     * Clause SQL brute de périmètre (pour les requêtes DBAL).
     *
     * @return array{0:string,1:array}
     */
    private function scopeSql(User $user): array
    {
        if ($user->isUser()) {
            return ['created_by = :uid', ['uid' => $user->getId()]];
        }
        if ($user->isTechnician()) {
            return ['assigned_to = :uid', ['uid' => $user->getId()]];
        }
        return ['TRUE', []];
    }
}
