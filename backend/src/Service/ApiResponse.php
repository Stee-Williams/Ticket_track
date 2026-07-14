<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Fabrique de réponses JSON normalisées : { success, message, data } ou
 * { success, message, errors }. Contrat identique à l'ancien backend.
 */
class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function error(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }
}
