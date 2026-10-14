<?php

declare(strict_types=1);

namespace Omi\OltManager;

use Omi\Http\HttpRequest;
use Omi\Http\HttpResult;
use Omi\Http\HttpTransport;

/**
 * Jedyny klient HTTP do OltManagera. Zawsze dokleja token API po stronie serwera -
 * token nigdy nie trafia do przeglądarki.
 */
class OltManagerClient
{
    public const SSO_TICKET_PATH = '/api/v1/integration/sso/ticket';
    public const SYNC_PATH = '/api/v1/integration/sync';

    private string $baseUrl;
    private string $token;
    private HttpTransport $transport;

    public function __construct(string $baseUrl, string $token, HttpTransport $transport)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
        $this->transport = $transport;
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->token !== '';
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Buduje pełny URL dla ścieżki względnej (ścieżka musi być wcześniej zwalidowana).
     */
    public function url(string $relativePath): string
    {
        return $this->baseUrl . '/' . ltrim($relativePath, '/');
    }

    public function get(string $relativePath, int $timeout = 30): HttpResult
    {
        return $this->transport->send(new HttpRequest('GET', $this->url($relativePath), $this->headers(), null, $timeout));
    }

    /**
     * @param array<mixed>|null $json
     */
    public function postJson(string $relativePath, ?array $json, int $timeout = 30): HttpResult
    {
        $body = $json === null ? '{}' : (string) json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $this->transport->send(new HttpRequest('POST', $this->url($relativePath), $this->headers(), $body, $timeout));
    }

    /**
     * Wymusza synchronizację po stronie OltManagera (type = customer|device).
     */
    public function triggerSync(string $type, int $timeout = 5): HttpResult
    {
        return $this->get(ltrim(self::SYNC_PATH, '/') . '?type=' . rawurlencode($type), $timeout);
    }

    /**
     * @return list<string>
     */
    private function headers(): array
    {
        return [
            'X-AUTH-TOKEN: ' . $this->token,
            'Accept: application/json',
            'Content-Type: application/json',
        ];
    }
}
