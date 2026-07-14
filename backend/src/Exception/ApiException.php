<?php

namespace App\Exception;

/**
 * Exception métier renvoyée sous forme de réponse JSON normalisée
 * par l'ExceptionSubscriber.
 */
class ApiException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 400,
        private readonly mixed $errors = null,
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): mixed
    {
        return $this->errors;
    }
}
