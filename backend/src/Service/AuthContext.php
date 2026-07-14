<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\ApiException;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Fournit l'utilisateur authentifié à partir du jeton JWT (entête Bearer)
 * et applique les contrôles de rôle.
 */
class AuthContext
{
    private ?User $current = null;
    private bool $resolved = false;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly JwtService $jwt,
        private readonly UserRepository $users,
    ) {
    }

    public function getUser(): ?User
    {
        if ($this->resolved) {
            return $this->current;
        }
        $this->resolved = true;

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        $header = $request->headers->get('Authorization', '');
        if (!preg_match('/Bearer\s+(.+)$/i', $header, $m)) {
            return null;
        }

        $payload = $this->jwt->decode(trim($m[1]));
        if ($payload === null || !isset($payload['id'])) {
            return null;
        }

        $this->current = $this->users->find((int) $payload['id']);
        return $this->current;
    }

    public function requireAuth(): User
    {
        $user = $this->getUser();
        if (!$user) {
            throw new ApiException('Jeton d\'authentification manquant ou invalide.', 401);
        }
        return $user;
    }

    /**
     * @param string[] $roles
     */
    public function requireRole(array $roles): User
    {
        $user = $this->requireAuth();
        if (!in_array($user->getRole(), $roles, true)) {
            throw new ApiException('Accès refusé : privilèges insuffisants.', 403);
        }
        return $user;
    }
}
