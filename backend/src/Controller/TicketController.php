<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\TicketComment;
use App\Entity\TicketHistory;
use App\Exception\ApiException;
use App\Repository\TicketCommentRepository;
use App\Repository\TicketHistoryRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Service\ApiResponse;
use App\Security\TicketPolicy;
use App\Service\AuthContext;
use App\Service\EntityNormalizer;
use App\Service\ExportService;
use App\Service\NotificationService;
use App\Service\TicketHistoryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tickets')]
class TicketController
{
    private const MAX_EXPORT_TICKETS = 5000;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TicketRepository $tickets,
        private readonly TicketCommentRepository $comments,
        private readonly TicketHistoryRepository $historyRepo,
        private readonly UserRepository $users,
        private readonly AuthContext $auth,
        private readonly TicketPolicy $policy,
        private readonly NotificationService $notifier,
        private readonly TicketHistoryService $history,
        private readonly ExportService $export,
        private readonly EntityNormalizer $normalizer,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $user    = $this->auth->requireAuth();
        $filters = $this->filtersFrom($request);

        $page  = max(1, (int) $request->query->get('page', 1));
        $limit = (int) $request->query->get('limit', 10);
        $limit = max(1, min($limit, 100));

        $result = $this->tickets->paginateForUser($user, $filters, $page, $limit);
        $data   = array_map(fn (array $row) => $this->normalizer->ticketList($row), $result['items']);

        $total = $result['total'];
        $pages = (int) max(1, ceil($total / $limit));

        return ApiResponse::success([
            'tickets'    => $data,
            'pagination' => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => $pages,
            ],
        ]);
    }

    #[Route('/export', methods: ['GET'])]
    public function exportTickets(Request $request): Response
    {
        $user    = $this->auth->requireAuth();
        $filters = $this->filtersFrom($request);
        $format  = strtolower((string) $request->query->get('format', 'xlsx'));

        $tickets = $this->tickets->findForExport($user, $filters, self::MAX_EXPORT_TICKETS + 1);
        if (count($tickets) > self::MAX_EXPORT_TICKETS) {
            throw new ApiException(
                'L’export est limité à 5 000 tickets. Ajoutez un filtre pour réduire le résultat.',
                422
            );
        }

        return match ($format) {
            'pdf'          => $this->export->toPdf($tickets),
            'xlsx', 'excel' => $this->export->toExcel($tickets),
            default        => throw new ApiException('Format d\'export non supporté (xlsx ou pdf).', 422),
        };
    }

    #[Route('', methods: ['POST'])]
    public function store(Request $request): JsonResponse
    {
        $user = $this->auth->requireAuth();
        $body = $this->body($request);

        $title       = trim($body['title'] ?? '');
        $description = trim($body['description'] ?? '');
        $category    = trim($body['category'] ?? '') ?: 'Général';
        $priority    = $body['priority'] ?? 'normale';

        $errors = [];
        if ($title === '') {
            $errors['title'] = 'Le titre est requis.';
        }
        if ($description === '') {
            $errors['description'] = 'La description est requise.';
        }
        if (!in_array($priority, Ticket::PRIORITIES, true)) {
            $priority = 'normale';
        }
        if ($errors) {
            throw new ApiException('Données invalides.', 422, $errors);
        }

        $ticket = (new Ticket())
            ->setReference($this->tickets->generateReference())
            ->setTitle($title)
            ->setDescription($description)
            ->setCategory($category)
            ->setPriority($priority)
            ->setStatus(Ticket::STATUS_WAITING)
            ->setCreatedBy($user);

        $this->em->wrapInTransaction(function () use ($ticket, $user): void {
            $this->em->persist($ticket);
            $this->history->logCreated($ticket, $user);
            $this->notifier->notifyManagers(
                $ticket,
                "Nouveau ticket {$ticket->getReference()} : {$ticket->getTitle()}"
            );
        });

        return ApiResponse::success(['ticket' => $this->normalizer->ticket($ticket)], 'Ticket créé avec succès.', 201);
    }

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $user   = $this->auth->requireAuth();
        $ticket = $this->findTicket($id);
        $this->policy->assertCanView($user, $ticket);

        $comments = array_map(
            fn (TicketComment $c) => $this->normalizer->comment($c),
            $this->comments->findForTicket($ticket)
        );

        $history = array_map(
            fn (TicketHistory $h) => $this->normalizer->history($h),
            $this->historyRepo->findForTicket($ticket)
        );

        return ApiResponse::success([
            'ticket'   => $this->normalizer->ticket($ticket),
            'comments' => $comments,
            'history'  => $history,
        ]);
    }

    #[Route('/{id}/assign', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function assign(int $id, Request $request): JsonResponse
    {
        $user   = $this->auth->requireAuth();
        $this->policy->assertCanAssign($user);
        $ticket = $this->findTicket($id);
        $body   = $this->body($request);

        $technician = $this->users->find((int) ($body['assigned_to'] ?? 0));
        if (!$technician || !$technician->isTechnician()) {
            throw new ApiException('Technicien invalide.', 422);
        }

        $ticket->setAssignedTo($technician);
        $this->history->logAssigned($ticket, $user, $technician);
        if ($ticket->getStatus() === Ticket::STATUS_WAITING) {
            $ticket->setStatus(Ticket::STATUS_IN_PROGRESS);
            $this->history->logStatusChanged($ticket, $user, Ticket::STATUS_WAITING, Ticket::STATUS_IN_PROGRESS);
        }

        $this->notifier->notify($technician, $ticket, "Le ticket {$ticket->getReference()} vous a été attribué.");
        $this->notifier->notify($ticket->getCreatedBy(), $ticket, "Votre ticket {$ticket->getReference()} est pris en charge.");

        $this->em->flush();

        return ApiResponse::success(['ticket' => $this->normalizer->ticket($ticket)], 'Ticket attribué avec succès.');
    }

    #[Route('/{id}/status', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $user   = $this->auth->requireAuth();
        $ticket = $this->findTicket($id);
        $this->policy->assertCanChangeStatus($user, $ticket);
        $body   = $this->body($request);

        $status = $body['status'] ?? '';
        if (!in_array($status, Ticket::STATUSES, true)) {
            throw new ApiException('Statut invalide.', 422);
        }

        $previousStatus = $ticket->getStatus();
        $ticket->setStatus($status);
        $ticket->setResolvedAt($status === Ticket::STATUS_DONE ? new \DateTime() : null);

        if ($previousStatus !== $status) {
            $this->history->logStatusChanged($ticket, $user, $previousStatus, $status);
        }

        $labels = ['en_attente' => 'En attente', 'en_cours' => 'En cours', 'traite' => 'Traité'];
        $this->notifier->notify(
            $ticket->getCreatedBy(),
            $ticket,
            "Le statut de votre ticket {$ticket->getReference()} est passé à : {$labels[$status]}."
        );

        $this->em->flush();

        return ApiResponse::success(['ticket' => $this->normalizer->ticket($ticket)], 'Statut mis à jour.');
    }

    #[Route('/{id}/comments', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addComment(int $id, Request $request): JsonResponse
    {
        $user   = $this->auth->requireAuth();
        $ticket = $this->findTicket($id);
        $this->policy->assertCanComment($user, $ticket);
        $body   = $this->body($request);

        $message = trim($body['message'] ?? '');
        if ($message === '') {
            throw new ApiException('Le message ne peut pas être vide.', 422);
        }

        $comment = (new TicketComment())
            ->setTicket($ticket)
            ->setUser($user)
            ->setMessage($message);
        $this->em->persist($comment);
        $this->history->logCommented($ticket, $user);

        // Notifier l'autre partie concernée
        $recipients = [];
        $recipients[$ticket->getCreatedBy()->getId()] = $ticket->getCreatedBy();
        if ($ticket->getAssignedTo()) {
            $recipients[$ticket->getAssignedTo()->getId()] = $ticket->getAssignedTo();
        }
        unset($recipients[$user->getId()]);
        foreach ($recipients as $recipient) {
            $this->notifier->notify($recipient, $ticket, "Nouveau commentaire sur le ticket {$ticket->getReference()}.");
        }

        $this->em->flush();

        return ApiResponse::success(null, 'Commentaire ajouté.', 201);
    }

    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function destroy(int $id): JsonResponse
    {
        $user   = $this->auth->requireAuth();
        $ticket = $this->findTicket($id);
        $this->policy->assertCanDelete($user, $ticket);

        $this->em->remove($ticket);
        $this->em->flush();

        return ApiResponse::success(null, 'Ticket supprimé.');
    }

    // ------------------------------------------------------------

    private function findTicket(int $id): Ticket
    {
        $ticket = $this->tickets->find($id);
        if (!$ticket) {
            throw new ApiException('Ticket introuvable.', 404);
        }
        return $ticket;
    }

    private function body(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        return is_array($data) ? $data : [];
    }

    /**
     * @return array{status: ?string, priority: ?string, category: ?string, search: ?string}
     */
    private function filtersFrom(Request $request): array
    {
        return [
            'status'   => $request->query->get('status'),
            'priority' => $request->query->get('priority'),
            'category' => $request->query->get('category'),
            'search'   => $request->query->get('search'),
        ];
    }
}
