<?php

declare(strict_types=1);

namespace Omi\Pppoe;

use Omi\Lms\LmsApi;
use Omi\Util\MacAddressCorrection;

/**
 * Dane PPPoE (login = nazwa komputera, hasło komputera) po ID komputera albo po adresie MAC
 * (z opcjonalnym sprawdzeniem kilku sąsiednich adresów MAC w górę i w dół).
 */
final class PppoeCredentialsFinder
{
    public const MAX_NEIGHBOUR_MACS = 8;

    private LmsApi $lms;

    public function __construct(LmsApi $lms)
    {
        $this->lms = $lms;
    }

    /**
     * @return array{nodeId: int, login: mixed, password: mixed}|null
     */
    public function byNodeId(int $nodeId): ?array
    {
        if ($nodeId <= 0) {
            return null;
        }

        return self::format($this->lms->getNode($nodeId));
    }

    /**
     * @return array{nodeId: int, login: mixed, password: mixed}|null
     */
    public function byMac(string $mac, int $upMacs = 0, int $downMacs = 0): ?array
    {
        foreach (self::candidateMacs($mac, $upMacs, $downMacs) as $candidate) {
            $nodeId = $this->lms->getNodeIdByMac($candidate);
            if ($nodeId === null) {
                continue;
            }
            $credentials = self::format($this->lms->getNode($nodeId));
            if ($credentials !== null) {
                return $credentials;
            }
        }

        return null;
    }

    /**
     * Kolejność sprawdzania: adres bazowy, potem kolejne w górę, potem kolejne w dół.
     *
     * @return list<string>
     */
    public static function candidateMacs(string $mac, int $upMacs, int $downMacs): array
    {
        if (!MacAddressCorrection::isValid($mac)) {
            return [];
        }
        $baseMac = (string) MacAddressCorrection::correct($mac, 'canonical');
        $upMacs = self::clamp($upMacs);
        $downMacs = self::clamp($downMacs);

        $macs = [$baseMac];
        for ($i = 1; $i <= $upMacs; $i++) {
            $macs[] = (string) MacAddressCorrection::correct(MacAddressCorrection::modifyMacAddress($baseMac, $i), 'canonical');
        }
        for ($i = 1; $i <= $downMacs; $i++) {
            $macs[] = (string) MacAddressCorrection::correct(MacAddressCorrection::modifyMacAddress($baseMac, -$i), 'canonical');
        }

        return $macs;
    }

    private static function clamp(int $count): int
    {
        return max(0, min(self::MAX_NEIGHBOUR_MACS, $count));
    }

    /**
     * @param array<string, mixed>|null $node
     * @return array{nodeId: int, login: mixed, password: mixed}|null
     */
    private static function format(?array $node): ?array
    {
        if ($node === null || !isset($node['id'])) {
            return null;
        }

        return [
            'nodeId' => (int) $node['id'],
            'login' => $node['name'] ?? null,
            'password' => $node['passwd'] ?? null,
        ];
    }
}
