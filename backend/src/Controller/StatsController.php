<?php

namespace App\Controller;

use App\Repository\TicketRepository;
use App\Service\ApiResponse;
use App\Service\AuthContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class StatsController
{
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly AuthContext $auth,
    ) {
    }

    #[Route('/api/stats', methods: ['GET'])]
    public function overview(): JsonResponse
    {
        $user = $this->auth->requireAuth();

        // Périmètre : le gestionnaire voit tout, sinon on filtre par utilisateur
        $scope = $user->isManager() ? null : $user;

        $byStatusRaw = $this->tickets->countByField('status', $scope);
        $byStatus = [
            'en_attente' => $byStatusRaw['en_attente'] ?? 0,
            'en_cours'   => $byStatusRaw['en_cours'] ?? 0,
            'traite'     => $byStatusRaw['traite'] ?? 0,
        ];
        $total = array_sum($byStatus);

        $byPriorityRaw = $this->tickets->countByField('priority', $scope);
        $byPriority = [
            'basse'   => $byPriorityRaw['basse'] ?? 0,
            'normale' => $byPriorityRaw['normale'] ?? 0,
            'haute'   => $byPriorityRaw['haute'] ?? 0,
            'urgente' => $byPriorityRaw['urgente'] ?? 0,
        ];

        $byCategoryRaw = $this->tickets->countByField('category', $scope);
        arsort($byCategoryRaw);
        $byCategory = [];
        foreach ($byCategoryRaw as $category => $count) {
            $byCategory[] = ['category' => $category, 'total' => $count];
        }

        $resolutionRate = $total > 0 ? round(($byStatus['traite'] / $total) * 100, 1) : 0;

        return ApiResponse::success([
            'total'                => $total,
            'by_status'            => $byStatus,
            'by_priority'          => $byPriority,
            'by_category'          => $byCategory,
            'avg_resolution_hours' => $this->tickets->averageResolutionHours($scope),
            'resolution_rate'      => $resolutionRate,
            'trend'                => $this->tickets->creationTrend($scope),
        ]);
    }
}
