<?php

declare(strict_types=1);

namespace Omi\Repository;

use Omi\Lms\Database;

/**
 * Adresy klientów (domyślny adres lokalizacji, w drugiej kolejności adres korespondencyjny)
 * ładowane jednym zapytaniem zamiast GetCustomerAddress() + zapytania o adres per klient.
 */
class CustomerAddressBook
{
    private Database $db;
    private int $defaultLocationType;
    private int $billingType;

    /** @var array<int, array<int, list<array<string, mixed>>>>|null customer_id => type => wiersze */
    private ?array $rows = null;

    public function __construct(Database $db, ?int $defaultLocationType = null, ?int $billingType = null)
    {
        $this->db = $db;
        $this->defaultLocationType = $defaultLocationType ?? (defined('DEFAULT_LOCATION_ADDRESS') ? (int) constant('DEFAULT_LOCATION_ADDRESS') : 3);
        $this->billingType = $billingType ?? (defined('BILLING_ADDRESS') ? (int) constant('BILLING_ADDRESS') : 1);
    }

    /**
     * Odpowiednik GetCustomerAddress(DEFAULT_LOCATION_ADDRESS) z fallbackiem na BILLING_ADDRESS.
     */
    public function locationAddressId(int $customerId): ?int
    {
        foreach ([$this->defaultLocationType, $this->billingType] as $type) {
            $row = $this->firstRow($customerId, $type);
            if ($row !== null && !empty($row['address_id'])) {
                return (int) $row['address_id'];
            }
        }

        return null;
    }

    /**
     * Identyfikatory TERYT adresu w formacie "locationAddress" z legacy API
     * (cityIdent, streetIdent, location_house) albo [] gdy adres nie ma miejscowości TERYT.
     *
     * @return array<string, mixed>
     */
    public function locationAddress(int $customerId): array
    {
        $addressId = $this->locationAddressId($customerId);

        return $addressId === null ? [] : $this->identsForAddress($customerId, $addressId);
    }

    /**
     * Odpowiednik LMSCustomerManager::getAddressForCustomerStuff().
     */
    public function locationText(int $customerId): ?string
    {
        $row = $this->lastRow($customerId, $this->defaultLocationType) ?? $this->lastRow($customerId, $this->billingType);
        if ($row === null) {
            return null;
        }

        $location = $row['location'] ?? null;
        if ($row['city_id'] === null || $row['city_id'] === '') {
            return $location === null ? null : (string) $location;
        }

        return function_exists('trans') ? trans('$a (TERYT)', $location) : $location . ' (TERYT)';
    }

    /**
     * @return array<string, mixed>
     */
    private function identsForAddress(int $customerId, int $addressId): array
    {
        foreach ($this->rowsFor($customerId) as $rows) {
            foreach ($rows as $row) {
                if ((int) $row['address_id'] !== $addressId || $row['vaddress_id'] === null) {
                    continue;
                }

                return self::formatIdents($row);
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function formatIdents(array $row): array
    {
        if (!isset($row['cityident']) || !array_key_exists('streetident', $row)) {
            return [];
        }

        return [
            'cityIdent' => (int) $row['cityident'],
            'streetIdent' => $row['streetident'] !== null ? (int) $row['streetident'] : null,
            'location_house' => $row['house'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function firstRow(int $customerId, int $type): ?array
    {
        $rows = $this->rowsFor($customerId)[$type] ?? [];

        return $rows === [] ? null : $rows[0];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lastRow(int $customerId, int $type): ?array
    {
        $rows = $this->rowsFor($customerId)[$type] ?? [];

        return $rows === [] ? null : $rows[count($rows) - 1];
    }

    /**
     * @return array<int, list<array<string, mixed>>>
     */
    private function rowsFor(int $customerId): array
    {
        if ($this->rows === null) {
            $this->load();
        }

        return $this->rows[$customerId] ?? [];
    }

    private function load(): void
    {
        $this->rows = [];
        $result = $this->db->getAll(
            'SELECT ca.id, ca.customer_id, ca.type, ca.address_id,
                a.id AS vaddress_id, a.house, a.location, a.city_id,
                lc.ident AS cityident, lst.ident AS streetident
            FROM customer_addresses ca
            LEFT JOIN vaddresses a ON a.id = ca.address_id
            LEFT JOIN location_cities lc ON lc.id = a.city_id
            LEFT JOIN location_streets lst ON lst.id = a.street_id
            WHERE ca.type IN (?, ?)
            ORDER BY ca.customer_id, ca.type, ca.id',
            [$this->defaultLocationType, $this->billingType]
        );

        foreach ($result as $row) {
            $this->rows[(int) $row['customer_id']][(int) $row['type']][] = $row;
        }
    }
}
