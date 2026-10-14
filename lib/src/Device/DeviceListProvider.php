<?php

declare(strict_types=1);

namespace Omi\Device;

use Omi\Lms\LmsApi;
use Omi\Repository\NetDevRepository;

/**
 * Listy komputerów i urządzeń sieciowych (legacy getDevices / getNetworkDevices)
 * oraz połączenia ONU wyznaczane z opisów (getNetworkDeviceConnections*).
 */
final class DeviceListProvider
{
    private LmsApi $lms;
    private NetDevRepository $netDevs;
    private DeviceDataBuilder $builder;
    private DescriptionToAddressConverter $converter;

    public function __construct(LmsApi $lms, NetDevRepository $netDevs, DeviceDataBuilder $builder, DescriptionToAddressConverter $converter)
    {
        $this->lms = $lms;
        $this->netDevs = $netDevs;
        $this->builder = $builder;
        $this->converter = $converter;
    }

    /**
     * @return array<int, array<string, mixed>> indeksowane po ID komputera
     */
    public function devices(): array
    {
        $devices = [];
        foreach ($this->nodes() as $node) {
            $devices[(int) $node['id']] = $this->builder->device($node);
        }

        return $devices;
    }

    /**
     * Urządzenia sieciowe z właścicielem (własnym albo podłączonego komputera); pozostałe są pomijane.
     *
     * @return array<int, array<string, mixed>> indeksowane po ID urządzenia
     */
    public function networkDevices(): array
    {
        $networkDevices = [];
        foreach ($this->netDevs->findAll() as $netDev) {
            $built = $this->builder->networkDevice($netDev);
            if ($built['ownerId'] === null) {
                continue;
            }
            $networkDevices[(int) $netDev['id']] = $built['networkDevice'];
        }

        return $networkDevices;
    }

    /**
     * Połączenia ONU z poprawnym adresem w opisie, indeksowane po adresie tekstowym.
     *
     * @return array<string, array<string, mixed>>
     */
    public function connections(): array
    {
        $connections = [];

        foreach ($this->nodes() as $node) {
            $address = $this->converter->convert($node['info'] ?? null);
            if ($address === null || array_key_exists('error', $address)) {
                continue;
            }
            $key = $address['stringAddress'];
            $connections[$key] = $connections[$key] ?? self::newConnection($address);
            $connections[$key]['devices'][] = $this->builder->device($node);
        }

        foreach ($this->netDevs->findAll() as $netDev) {
            $address = $this->converter->convert($netDev['description'] ?? null);
            if ($address === null || array_key_exists('error', $address)) {
                continue;
            }
            $key = $address['stringAddress'];
            $connections[$key] = $connections[$key] ?? self::newConnection($address);
            $connections[$key]['networkDevices'][] = $this->builder->networkDevice($netDev)['networkDevice'];
        }

        return $connections;
    }

    /**
     * Obiekty z błędnym adresem ONU w opisie - po jednym połączeniu na obiekt.
     *
     * @return list<array<string, mixed>>
     */
    public function connectionsWithError(): array
    {
        $connections = [];

        foreach ($this->nodes() as $node) {
            $address = $this->converter->convert($node['info'] ?? null, true);
            if ($address === null || !array_key_exists('error', $address)) {
                continue;
            }
            $device = $this->builder->device($node);
            $device['error'] = $address;
            $connection = self::newConnection($address);
            $connection['devices'][] = $device;
            $connections[] = $connection;
        }

        foreach ($this->netDevs->findAll() as $netDev) {
            $address = $this->converter->convert($netDev['description'] ?? null, true);
            if ($address === null || !array_key_exists('error', $address)) {
                continue;
            }
            $networkDevice = $this->builder->networkDevice($netDev)['networkDevice'];
            $networkDevice['error'] = $address;
            $connection = self::newConnection($address);
            $connection['networkDevices'][] = $networkDevice;
            $connections[] = $connection;
        }

        return $connections;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function nodes(): array
    {
        $nodes = [];
        foreach ($this->lms->getNodeList() as $node) {
            if (is_array($node) && isset($node['id'])) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /**
     * @param array<string, mixed> $address
     * @return array{address: array<string, mixed>, devices: list<array<string, mixed>>, networkDevices: list<array<string, mixed>>}
     */
    private static function newConnection(array $address): array
    {
        return ['address' => $address, 'devices' => [], 'networkDevices' => []];
    }
}
