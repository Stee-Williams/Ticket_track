<?php

namespace App\EventSubscriber;

use App\Exception\ApiException;
use App\Service\ApiResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Convertit toutes les exceptions en réponses JSON normalisées.
 */
class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly bool $debug = false)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onException'];
    }

    public function onException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();

        if ($e instanceof ApiException) {
            $event->setResponse(ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getErrors()));
            return;
        }

        if ($e instanceof HttpExceptionInterface) {
            $message = $e->getStatusCode() === 404 ? 'Ressource introuvable.' : $e->getMessage();
            $event->setResponse(ApiResponse::error($message, $e->getStatusCode()));
            return;
        }

        $message = $this->debug
            ? $e->getMessage()
            : 'Une erreur interne est survenue.';
        $event->setResponse(ApiResponse::error($message, 500));
    }
}
