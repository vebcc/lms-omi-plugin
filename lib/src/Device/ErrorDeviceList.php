<?php

declare(strict_types=1);

namespace Omi\Device;

use Omi\Lms\LmsApi;

/**
 * Wiersze listy "Urządzenia z błędami" z połączeń z błędnym adresem ONU.
 */
final class ErrorDeviceList
{
    private LmsApi $lms;

    public function __construct(LmsApi $lms)
    {
        $this->lms = $lms;
    }

    /**
     * @param list<array<string, mixed>> $connections wynik DeviceListProvider::connectionsWithError()
     * @return list<array<string, mixed>>
     */
    public function build(array $connections): array
    {
        $rows = [];
        foreach ($connections as $connection) {
            if (!empty($connection['devices'])) {
                $rows[] = $this->row('device', $connection['devices'][0], 'node');
            } elseif (!empty($connection['networkDevices'])) {
                $rows[] = $this->row('networkDevice', $connection['networkDevices'][0], 'netdev');
            } else {
                $rows[] = [
                    'type' => 'error',
                    'lmsId' => null,
                    'error' => 'libraryFailed',
                    'address' => null,
                    'name' => null,
                    'link' => ['show' => '#', 'edit' => '#'],
                ];
            }
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $object
     * @return array<string, mixed>
     */
    private function row(string $type, array $object, string $modulePrefix): array
    {
        $id = (int) $object['lmsId'];
        $name = $modulePrefix === 'node' ? $this->lms->getNodeName($id) : $this->lms->getNetDevName($id);

        return [
            'type' => $type,
            'lmsId' => $id,
            'error' => $object['error']['error'] ?? null,
            'address' => $object['error']['address'] ?? null,
            'name' => $name,
            'link' => [
                'show' => '?m=' . $modulePrefix . 'info&id=' . $id,
                'edit' => '?m=' . $modulePrefix . 'edit&id=' . $id,
            ],
        ];
    }
}
