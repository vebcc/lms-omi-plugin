<?php

declare(strict_types=1);

namespace Omi\Legacy;

use Omi\Config\OmiConfig;
use Omi\Device\DeviceListProvider;
use Omi\Pppoe\PppoeCredentialsFinder;
use Omi\Repository\UserRepository;

/**
 * Adapter starego modułu ?m=omidatagetter&type=... z identycznym kształtem odpowiedzi.
 *
 * Tokeny autologowania (getMyToken / getUserTokens) są wydawane tylko, gdy
 * omi.legacy_login_fallback = true - po wyłączeniu przez admina stary OltManager
 * dostaje błąd w formacie legacy (który obsługuje i loguje).
 *
 * @deprecated używaj ?m=omiapi (API v3) i SSO
 */
final class LegacyOmiDataGetter
{
    private OmiConfig $config;
    private DeviceListProvider $devices;
    private PppoeCredentialsFinder $pppoe;
    private UserRepository $users;
    private ?int $currentUserId;

    public function __construct(
        OmiConfig $config,
        DeviceListProvider $devices,
        PppoeCredentialsFinder $pppoe,
        UserRepository $users,
        ?int $currentUserId
    ) {
        $this->config = $config;
        $this->devices = $devices;
        $this->pppoe = $pppoe;
        $this->users = $users;
        $this->currentUserId = $currentUserId;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<mixed>
     */
    public function handle(string $type, array $params): array
    {
        switch ($type) {
            case 'getNetworkDeviceConnections':
            case 'getNetworkDeviceConnectionsWithError':
                if (($params['version'] ?? 'description') !== 'description') {
                    return ['exception' => 'This provider version is not available', 'code' => 11];
                }

                return $type === 'getNetworkDeviceConnections'
                    ? $this->devices->connections()
                    : $this->devices->connectionsWithError();
            case 'getMyToken':
                return [$this->myToken()];
            case 'getMyLogin':
                return [$this->myLogin()];
            case 'getUserTokens':
                return $this->userTokens();
            case 'getPPPoECredentials':
                return $this->pppoeCredentials($params);
            case 'getDevices':
                $devices = $this->devices->devices();

                return $devices === [] ? ['exception' => 'Cant get devices', 'code' => 15] : $devices;
            case 'getNetworkDevices':
                $networkDevices = $this->devices->networkDevices();

                return $networkDevices === [] ? ['exception' => 'Cant get networkDevices', 'code' => 16] : $networkDevices;
            default:
                return ['exception' => 'type parameter required', 'code' => 10];
        }
    }

    /**
     * @return string|array<string, mixed>
     */
    private function myToken()
    {
        $token = null;
        if ($this->config->legacyLoginFallback() && $this->currentUserId !== null) {
            $token = $this->users->tokenForUser($this->currentUserId);
        }

        return $token ?? ['exception' => 'Cant get token for your account', 'code' => 12];
    }

    /**
     * @return string|array<string, mixed>
     */
    private function myLogin()
    {
        $login = $this->currentUserId !== null ? $this->users->loginById($this->currentUserId) : null;

        return $login ?? ['exception' => 'Cant get login for your account', 'code' => 12];
    }

    /**
     * @return array<string, mixed>
     */
    private function userTokens(): array
    {
        $tokens = $this->config->legacyLoginFallback() ? $this->users->legacyTokensOfActiveUsers() : [];

        return $tokens === [] ? ['exception' => 'Cant get tokens', 'code' => 13] : $tokens;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function pppoeCredentials(array $params): array
    {
        $credentials = null;
        if (isset($params['mac']) && is_string($params['mac'])) {
            $credentials = $this->pppoe->byMac(
                $params['mac'],
                self::intParam($params, 'upMacs'),
                self::intParam($params, 'downMacs')
            );
        } elseif (isset($params['nodeId']) && is_string($params['nodeId']) && ctype_digit($params['nodeId'])) {
            $credentials = $this->pppoe->byNodeId((int) $params['nodeId']);
        }

        if ($credentials === null) {
            return ['exception' => 'Cant get PPPoE credentials for your account', 'code' => 14];
        }

        return ['username' => $credentials['login'], 'password' => $credentials['password']];
    }

    /**
     * @param array<string, mixed> $params
     */
    private static function intParam(array $params, string $name): int
    {
        return isset($params[$name]) && is_string($params[$name]) && ctype_digit($params[$name]) ? (int) $params[$name] : 0;
    }
}
