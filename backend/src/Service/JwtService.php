<?php

namespace App\Service;

/**
 * Implémentation minimale de JWT (HMAC-SHA256), sans dépendance externe
 * ni extension particulière (pas besoin de ext-sodium).
 */
class JwtService
{
    public function __construct(
        private readonly string $secret,
        private readonly int $ttl = 86400,
    ) {
    }

    public function encode(array $payload): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];

        $payload['iat'] = time();
        $payload['exp'] = time() + $this->ttl;

        $segments = [
            $this->base64UrlEncode(json_encode($header)),
            $this->base64UrlEncode(json_encode($payload)),
        ];

        $signingInput = implode('.', $segments);
        $signature    = hash_hmac('sha256', $signingInput, $this->secret, true);
        $segments[]   = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header64, $payload64, $signature64] = $parts;

        $expected = $this->base64UrlEncode(
            hash_hmac('sha256', $header64 . '.' . $payload64, $this->secret, true)
        );

        if (!hash_equals($expected, $signature64)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($payload64), true);
        if (!is_array($payload)) {
            return null;
        }
        if (isset($payload['exp']) && time() > $payload['exp']) {
            return null;
        }

        return $payload;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
