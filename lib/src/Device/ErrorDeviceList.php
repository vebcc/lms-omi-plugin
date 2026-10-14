<?php

declare(strict_types=1);

namespace Omi\Device;

use Omi\Lms\LmsApi;
use Omi\Repository\NetDevRepository;

/**
 * Wiersze listy "Urządzenia z błędami": komputery i urządzenia sieciowe z błędnie zapisanym
 * adresem ONU w pierwszej linii opisu (np. "TU_1/2/3" bez numeru ONU).
 */
final class ErrorDeviceList
{
    private LmsApi $lms;
    private NetDevRepository $netDevs;
    private DescriptionToAddressConverter $converter;

    public function __construct(LmsApi $lms, NetDevRepository $netDevs, DescriptionToAddressConverter $converter)
    {
        $this->lms = $lms;
        $this->netDevs = $netDevs;
        $this->converter = $converter;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function build(): array
    {
        $rows = [];

        foreach ($this->lms->getNodeList() as $node) {
            if (!is_array($node) || !isset($node['id'])) {
                continue;
            }
            $row = $this->row('device', 'node', $node, $node['info'] ?? null);
            if ($row !== null) {
                $rows[] = $row;
            }
        }

        foreach ($this->netDevs->findAll() as $netDev) {
            $row = $this->row('networkDevice', 'netdev', $netDev, $netDev['description'] ?? null);
            if ($row !== null) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $object
     * @return array<string, mixed>|null
     */
    private function row(string $type, string $modulePrefix, array $object, ?string $description): ?array
    {
        $result = $this->converter->convert($description, true);
        if ($result === null || !array_key_exists('error', $result)) {
            return null;
        }
        $id = (int) $object['id'];

        return [
            'type' => $type,
            'lmsId' => $id,
            'error' => $result['error'],
            'address' => $result['address'] ?? null,
            'name' => $object['name'] ?? null,
            'link' => [
                'show' => '?m=' . $modulePrefix . 'info&id=' . $id,
                'edit' => '?m=' . $modulePrefix . 'edit&id=' . $id,
            ],
        ];
    }
}
