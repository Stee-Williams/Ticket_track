<?php

namespace App\Controller;

use App\Entity\User;
use App\Exception\ApiException;
use App\Repository\UserRepository;
use App\Service\ApiResponse;
use App\Service\AuthContext;
use App\Service\EntityNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users')]
class UserController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly AuthContext $auth,
        private readonly EntityNormalizer $normalizer,
    ) {
    }

    #[Route('/technicians', methods: ['GET'])]
    public function technicians(): JsonResponse
    {
        $this->auth->requireRole([User::ROLE_MANAGER]);
        return ApiResponse::success(['technicians' => $this->users->findTechniciansWithLoad()]);
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $this->auth->requireRole([User::ROLE_MANAGER]);

        $users = $this->users->findBy([], ['createdAt' => 'DESC']);
        $data = array_map(fn (User $u) => $this->normalizer->user($u, true), $users);

        return ApiResponse::success(['users' => $data]);
    }

    #[Route('/{id}/role', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateRole(int $id, Request $request): JsonResponse
    {
        $this->auth->requireRole([User::ROLE_MANAGER]);

        $body = json_decode($request->getContent(), true) ?: [];
        $role = $body['role'] ?? '';
        if (!in_array($role, User::ROLES, true)) {
            throw new ApiException('Rôle invalide.', 422);
        }

        $user = $this->users->find($id);
        if (!$user) {
            throw new ApiException('Utilisateur introuvable.', 404);
        }
        $user->setRole($role);
        $this->em->flush();

        return ApiResponse::success(null, 'Rôle mis à jour.');
    }
}
