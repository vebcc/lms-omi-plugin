<?php

declare(strict_types=1);

namespace Omi\Sso;

use Omi\Config\OmiConfig;
use Omi\OltManager\OltManagerClient;
use Omi\Proxy\RelativePath;
use Omi\Support\Logger;
use Omi\Support\Strings;

/**
 * Wyznacza adres przekierowania użytkownika do OltManagera (kontrakt API v3, sekcja 4.3).
 *
 * 1. Auto-login wyłączony -> zwykły adres OltManagera.
 * 2. Auto-login włączony -> jednorazowy ticket SSO wystawiony server-side.
 * 3. OltManager bez endpointu SSO (404) -> stary sposób z tokenami w URL, tylko gdy
 *    omi.legacy_login_fallback = true.
 * 4. Każdy inny błąd -> zwykły adres + wpis w error_log (bez tokenów).
 */
class SsoRedirector
{
    private const TICKET_TIMEOUT = 10;

    private OmiConfig $config;
    private OltManagerClient $client;
    private LegacyTokenProvider $legacyTokens;
    private Logger $logger;

    public function __construct(OmiConfig $config, OltManagerClient $client, LegacyTokenProvider $legacyTokens, Logger $logger)
    {
        $this->config = $config;
        $this->client = $client;
        $this->legacyTokens = $legacyTokens;
        $this->logger = $logger;
    }

    public function redirectUrl(string $rawPath, ?CurrentUser $user): string
    {
        $target = RelativePath::target($rawPath);
        $plainUrl = $this->client->baseUrl() . $target;

        if (!$this->config->automaticLogin() || $user === null || $user->login() === '' || !$this->client->isConfigured()) {
            return $plainUrl;
        }

        $result = $this->client->postJson(
            OltManagerClient::SSO_TICKET_PATH,
            ['login' => $user->login(), 'target' => $target],
            self::TICKET_TIMEOUT
        );

        if (!$result->isTransportOk()) {
            $this->logger->error('SSO: ticket request failed: ' . $result->error());

            return $plainUrl;
        }

        $json = $result->json();

        if ($result->status() === 201 || $result->status() === 200) {
            $loginUrl = is_array($json) && isset($json['loginUrl']) && is_string($json['loginUrl']) ? $json['loginUrl'] : '';
            if (self::isSafeLoginUrl($loginUrl)) {
                return $this->client->baseUrl() . $loginUrl;
            }
            $this->logger->error('SSO: OltManager returned an invalid loginUrl');

            return $plainUrl;
        }

        if ($result->status() === 404 && self::errorCode($json) !== 'user_not_found') {
            // stary OltManager bez endpointu SSO
            return $this->legacyUrl($plainUrl, $user);
        }

        $this->logger->error(sprintf(
            'SSO: ticket request rejected: HTTP %d%s',
            $result->status(),
            self::errorCode($json) !== '' ? ' (' . self::errorCode($json) . ')' : ''
        ));

        return $plainUrl;
    }

    private function legacyUrl(string $plainUrl, CurrentUser $user): string
    {
        if (!$this->config->legacyLoginFallback()) {
            return $plainUrl;
        }

        $userToken = $this->legacyTokens->tokenForUser($user->id());
        if ($userToken === null || $userToken === '') {
            return $plainUrl;
        }

        return self::buildLegacyLoginUrl($plainUrl, $this->config->oltManagerToken(), $userToken);
    }

    public static function buildLegacyLoginUrl(string $url, string $apiToken, string $userToken): string
    {
        return Strings::appendQuery(
            $url,
            'x-auth-token=' . rawurlencode($apiToken) . '&x-auth-additional-token=' . rawurlencode($userToken)
        );
    }

    public static function isSafeLoginUrl(string $loginUrl): bool
    {
        return Strings::startsWith($loginUrl, '/') && RelativePath::normalize($loginUrl) !== null;
    }

    /**
     * @param array<mixed>|null $json
     */
    private static function errorCode(?array $json): string
    {
        if (is_array($json) && isset($json['error']) && is_array($json['error']) && isset($json['error']['code'])) {
            return (string) $json['error']['code'];
        }

        return '';
    }
}
