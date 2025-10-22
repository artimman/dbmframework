<?php
/**
 * Application: DbM Framework
 * A lightweight PHP framework for building web applications.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 */

declare(strict_types=1);

namespace Dbm\Api;

use Dbm\Classes\Http\Response;

class ApiJwtService
{
    private string $secret;
    private int $expiration;

    public function __construct(?string $secret = null, ?int $expiration = null)
    {
        if (getenv('API_ENABLED') !== 'true') {
            Response::json(['error' => 'API is disabled'], 503);
            exit;
        }

        $this->secret = $secret ?? (getenv('API_JWT_SECRET') ?: 'default-secret-key');
        $this->expiration = $expiration ?? (int) (getenv('API_JWT_EXPIRATION') ?: 3600);
    }

    /**
     * Generuje nowy token JWT.
     */
    public function generateToken(array $payload): string
    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload['exp'] = time() + $this->expiration;
        $payloadEncoded = base64_encode(json_encode($payload));

        $signature = hash_hmac('sha256', "$header.$payloadEncoded", $this->secret, true);

        return "$header.$payloadEncoded." . base64_encode($signature);
    }

    /**
     * Walidacja tokena JWT.
     */
    public function validateToken(string $token): bool
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        [$header, $payload, $signature] = $parts;
        $expected = base64_encode(hash_hmac('sha256', "$header.$payload", $this->secret, true));

        if (!hash_equals($expected, $signature)) {
            return false;
        }

        $data = json_decode(base64_decode($payload), true);
        return isset($data['exp']) && $data['exp'] > time();
    }

    /**
     * Dekodowanie payloadu z JWT.
     */
    public function decodeToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(base64_decode($parts[1]), true);
        return $payload;
    }

    /**
     * Odświeża token na podstawie istniejącego.
     */
    public function refreshToken(string $oldToken): ?string
    {
        $payload = $this->decodeToken($oldToken);

        if (!$payload) {
            return null;
        }

        unset($payload['exp']);
        return $this->generateToken($payload);
    }

    /**
     * Sprawdza, czy token ma daną rolę.
     */
    public function hasRole(string $token, string $requiredRole): bool
    {
        $payload = $this->decodeToken($token);

        if (!$payload) {
            return false;
        }

        return isset($payload['role']) && $payload['role'] === $requiredRole;
    }

    /**
     * Sprawdza, czy token ma dany scope.
     */
    public function hasScope(string $token, string $requiredScope): bool
    {
        $payload = $this->decodeToken($token);

        if (!$payload) {
            return false;
        }

        return in_array($requiredScope, $payload['scopes'] ?? [], true);
    }
}
