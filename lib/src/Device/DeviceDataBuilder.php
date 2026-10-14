<?php

declare(strict_types=1);

namespace Omi\Device;

use Omi\Repository\CustomerDirectory;
use Omi\Repository\NetDevLocationResolver;
use Omi\Repository\NodeCredentials;

/**
 * Budowa obiektów "device" (komputer) i "networkDevice" (urządzenie sieciowe)
 * w formacie legacy omidatagetter.
 */
final class DeviceDataBuilder
{
    private NodeCredentials $credentials;
    private CustomerDirectory $customers;
    private NetDevLocationResolver $locationResolver;

    public function __construct(NodeCredentials $credentials, CustomerDirectory $customers, NetDevLocationResolver $locationResolver)
    {
        $this->credentials = $credentials;
        $this->customers = $customers;
        $this->locationResolver = $locationResolver;
    }

    /**
     * @param array<string, mixed> $node element GetNodeList()
     * @return array<string, mixed>
     */
    public function device(array $node): array
    {
        $credentials = $this->credentials->forNode((int) $node['id']);

        return [
            'lmsId' => !empty($node['id']) ? (int) $node['id'] : null,
            'name' => $node['name'] ?? null,
            'netDev' => !empty($node['netdev']) ? (int) $node['netdev'] : null,
            'netNode' => !empty($node['netnodeid']) ? (int) $node['netnodeid'] : null,
            'login' => $credentials['name'] ?? null,
            'password' => $credentials['passwd'] ?? null,
            'data' => [
                'ip' => $node['ip'] ?? null,
                'mac' => $node['mac'] ?? null,
                'serial' => null,
                'producer' => null,
                'model' => null,
            ],
            'owner' => $this->customers->owner(!empty($node['ownerid']) ? (int) $node['ownerid'] : null),
            'address' => [
                'cityIdent' => (int) ($node['city_ident'] ?? 0),
                'streetIdent' => (int) ($node['street_ident'] ?? 0),
                'location_house' => $node['location_house'] ?? null,
                'longitude' => $node['longitude'] ?? null,
                'latitude' => $node['latitude'] ?? null,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $netDev wiersz NetDevRepository
     * @return array{networkDevice: array<string, mixed>, ownerId: int|null}
     */
    public function networkDevice(array $netDev): array
    {
        $resolved = $this->locationResolver->resolve($netDev);

        return [
            'networkDevice' => [
                'lmsId' => !empty($netDev['id']) ? (int) $netDev['id'] : null,
                'name' => $netDev['name'],
                'netNode' => !empty($netDev['netnodeid']) ? (int) $netDev['netnodeid'] : null,
                'data' => [
                    'ip' => null,
                    'mac' => null,
                    'serial' => $netDev['serialnumber'],
                    'producer' => $netDev['producer'],
                    'model' => $netDev['model'],
                ],
                'owner' => $this->customers->owner($resolved['ownerId']),
                'address' => $resolved['locationAddress'],
            ],
            'ownerId' => $resolved['ownerId'],
        ];
    }
}
