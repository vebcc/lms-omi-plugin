<?php

declare(strict_types=1);

namespace Omi\Proxy;

use Omi\Config\OmiConfig;
use Omi\Http\HttpResult;
use Omi\OltManager\OltManagerClient;
use Omi\Sso\CurrentUser;
use Omi\Sso\SsoRedirector;

/**
 * Logika modułów omiapiproxy (odczyt, GET) i omiapiaction (akcje ONU, POST).
 * Token OltManagera zostaje na serwerze LMS.
 */
final class OltManagerProxy
{
    private const READ_TIMEOUT = 30;
    private const ACTION_TIMEOUT = 60;

    private OmiConfig $config;
    private OltManagerClient $client;
    private SsoRedirector $sso;

    public function __construct(OmiConfig $config, OltManagerClient $client, SsoRedirector $sso)
    {
        $this->config = $config;
        $this->client = $client;
        $this->sso = $sso;
    }

    /**
     * @param array<string, mixed> $query parametry GET
     */
    public function read(string $httpMethod, array $query, ?CurrentUser $user): ProxyResponse
    {
        if ($httpMethod !== 'GET') {
            return ProxyResponse::error(405, 'Method not allowed');
        }
        if (isset($query['method']) && strtoupper((string) $query['method']) !== 'GET') {
            return ProxyResponse::error(405, 'Only GET requests are proxied, use the action module');
        }
        if (!$this->client->isConfigured()) {
            return ProxyResponse::error(503, 'OltManager not configured');
        }

        $endpoint = RelativePath::normalize(isset($query['endpoint']) && is_string($query['endpoint']) ? $query['endpoint'] : '');
        if ($endpoint === null) {
            return ProxyResponse::error(400, 'Missing or invalid endpoint parameter');
        }

        if (isset($query['redirect']) && $query['redirect'] === '1') {
            return ProxyResponse::redirect($this->sso->redirectUrl($endpoint, $user));
        }

        $endpoint = IntegrationInstance::apply($endpoint, $this->config->integrationInstanceParam());

        return self::passThrough($this->client->get($endpoint, self::READ_TIMEOUT));
    }

    /**
     * @param array<string, mixed> $post parametry POST
     */
    public function action(string $httpMethod, array $post, bool $csrfValid): ProxyResponse
    {
        if ($httpMethod !== 'POST') {
            return ProxyResponse::error(405, 'Method not allowed');
        }
        if (!$csrfValid) {
            return ProxyResponse::error(403, 'Invalid or missing CSRF token, reload the page');
        }
        if (!$this->client->isConfigured()) {
            return ProxyResponse::error(503, 'OltManager not configured');
        }

        $endpoint = OnuActionEndpoint::normalize(isset($post['endpoint']) && is_string($post['endpoint']) ? $post['endpoint'] : '');
        if ($endpoint === null) {
            return ProxyResponse::error(400, 'Missing or invalid action endpoint');
        }

        return self::passThrough($this->client->postJson($endpoint, null, self::ACTION_TIMEOUT));
    }

    private static function passThrough(HttpResult $result): ProxyResponse
    {
        if (!$result->isTransportOk()) {
            return ProxyResponse::error(502, 'Upstream connection error');
        }
        if ($result->status() >= 300 && $result->status() < 400) {
            return ProxyResponse::error(502, 'Unexpected upstream redirect');
        }
        if ($result->status() < 100) {
            return ProxyResponse::error(502, 'Invalid upstream response');
        }

        return ProxyResponse::json($result->status(), $result->body());
    }
}
