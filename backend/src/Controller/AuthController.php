<?php

namespace App\Controller;

use App\Entity\User;
use App\Exception\ApiException;
use App\Repository\UserRepository;
use App\Service\ApiResponse;
use App\Service\AuthContext;
use App\Service\EntityNormalizer;
use App\Service\JwtService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
class AuthController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly JwtService $jwt,
        private readonly AuthContext $auth,
        private readonly EntityNormalizer $normalizer,
    ) {
    }

    #[Route('/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $body = $this->body($request);

        $name     = trim($body['name'] ?? '');
        $email    = strtolower(trim($body['email'] ?? ''));
        $password = $body['password'] ?? '';
        $phone    = trim($body['phone'] ?? '') ?: null;

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Le nom est requis.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Adresse e-mail invalide.';
        }
        if (strlen($password) < 6) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères.';
        }
        if ($errors) {
            throw new ApiException('Données invalides.', 422, $errors);
        }

        if ($this->users->findByEmail($email) !== null) {
            throw new ApiException('Cette adresse e-mail est déjà utilisée.', 409);
        }

        $user = (new User())
            ->setName($name)
            ->setEmail($email)
            ->setPassword(password_hash($password, PASSWORD_BCRYPT))
            ->setRole(User::ROLE_USER)
            ->setPhone($phone);

        $this->em->persist($user);
        $this->em->flush();

        return ApiResponse::success(
            ['user' => $this->normalizer->user($user), 'token' => $this->makeToken($user)],
            'Compte créé avec succès.',
            201
        );
    }

    #[Route('/login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $body = $this->body($request);
        $email    = strtolower(trim($body['email'] ?? ''));
        $password = $body['password'] ?? '';

        if ($email === '' || $password === '') {
            throw new ApiException('E-mail et mot de passe requis.', 422);
        }

        $user = $this->users->findByEmail($email);
        if (!$user || !password_verify($password, $user->getPassword())) {
            throw new ApiException('Identifiants incorrects.', 401);
        }

        return ApiResponse::success(
            ['user' => $this->normalizer->user($user), 'token' => $this->makeToken($user)],
            'Connexion réussie.'
        );
    }

    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->auth->requireAuth();
        return ApiResponse::success(['user' => $this->normalizer->user($user, true)]);
    }

    private function makeToken(User $user): string
    {
        return $this->jwt->encode([
            'id'    => $user->getId(),
            'email' => $user->getEmail(),
            'role'  => $user->getRole(),
            'name'  => $user->getName(),
        ]);
    }

    private function body(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        return is_array($data) ? $data : [];
    }
}
