<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Service\ApiResponse;
use App\Service\AuthContext;
use App\Service\EntityNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notifications')]
class NotificationController
{
    public function __construct(
        private readonly NotificationRepository $notifications,
        private readonly AuthContext $auth,
        private readonly EntityNormalizer $normalizer,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $user  = $this->auth->requireAuth();
        $items = $this->notifications->findForUser($user);

        $data   = array_map(fn (Notification $n) => $this->normalizer->notification($n), $items);
        $unread = 0;
        foreach ($items as $n) {
            if (!$n->isRead()) {
                $unread++;
            }
        }

        return ApiResponse::success(['notifications' => $data, 'unread' => $unread]);
    }

    #[Route('/read', methods: ['PUT'])]
    public function markAllRead(): JsonResponse
    {
        $user = $this->auth->requireAuth();
        $this->notifications->markAllRead($user);

        return ApiResponse::success(null, 'Notifications marquées comme lues.');
    }
}
