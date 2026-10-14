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
 * 3. Każdy błąd (w tym 404 starego OltManagera bez SSO) -> zwykły adres + wpis w error_log
 *    (bez tokenów).
 */
class SsoRedirector
{
    private const TICKET_TIMEOUT = 10;

    private OmiConfig $config;
    private OltManagerClient $client;
    private Logger $logger;

    public function __construct(OmiConfig $config, OltManagerClient $client, Logger $logger)
    {
        $this->config = $config;
        $this->client = $client;
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
        $errorCode = self::errorCode($json);

        if ($result->status() === 201 || $result->status() === 200) {
            $loginUrl = is_array($json) && isset($json['loginUrl']) && is_string($json['loginUrl']) ? $json['loginUrl'] : '';
            if (self::isSafeLoginUrl($loginUrl)) {
                return $this->client->baseUrl() . $loginUrl;
            }
            $this->logger->error('SSO: OltManager returned an invalid loginUrl');

            return $plainUrl;
        }

        if ($result->status() === 404 && $errorCode !== 'user_not_found') {
            $this->logger->error(
                'SSO: OltManager does not support SSO (HTTP 404 on ' . OltManagerClient::SSO_TICKET_PATH
                . '), automatic login skipped - update OltManager to a version with API v3 / SSO'
            );

            return $plainUrl;
        }

        $this->logger->error(sprintf(
            'SSO: ticket request rejected: HTTP %d%s',
            $result->status(),
            $errorCode !== '' ? ' (' . $errorCode . ')' : ''
        ));

        return $plainUrl;
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
