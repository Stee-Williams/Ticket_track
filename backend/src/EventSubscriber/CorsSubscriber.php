<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Gestion du CORS pour autoriser le frontend React (localhost:5173).
 * Remplace nelmio/cors-bundle (non installé) sans dépendance externe.
 */
class CorsSubscriber implements EventSubscriberInterface
{
    private const ALLOW_METHODS = 'GET, POST, PUT, DELETE, OPTIONS';
    private const ALLOW_HEADERS = 'Content-Type, Authorization';

    public function __construct(private readonly array $allowedOrigins)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité élevée pour court-circuiter les requêtes preflight
            KernelEvents::REQUEST  => ['onRequest', 9999],
            KernelEvents::RESPONSE => ['onResponse', -9999],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response('', 204, $this->headers($request->headers->get('Origin')));
            $event->setResponse($response);
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        $origin  = $event->getRequest()->headers->get('Origin');
        $headers = $this->headers($origin);
        foreach ($headers as $key => $value) {
            $event->getResponse()->headers->set($key, $value);
        }
    }

    private function headers(?string $origin): array
    {
        $headers = [
            'Access-Control-Allow-Methods'     => self::ALLOW_METHODS,
            'Access-Control-Allow-Headers'     => self::ALLOW_HEADERS,
            'Vary'                             => 'Origin',
        ];

        if ($origin !== null && in_array($origin, $this->allowedOrigins, true)) {
            $headers['Access-Control-Allow-Origin'] = $origin;
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        return $headers;
    }
}
