<?php

declare(strict_types=1);

namespace Omi\Repository;

use Omi\Lms\Database;

/**
 * Podstawowe dane klientów (właścicieli urządzeń) ładowane jednym zapytaniem.
 */
class CustomerDirectory
{
    private Database $db;

    /** @var array<int, array{id: int, name: mixed, secondName: mixed}>|null */
    private ?array $owners = null;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Właściciel w formacie legacy: ['id', 'name', 'secondName'] albo [] gdy brak.
     *
     * @return array<string, mixed>
     */
    public function owner(?int $customerId): array
    {
        if (!$customerId) {
            return [];
        }
        if ($this->owners === null) {
            $this->owners = [];
            foreach ($this->db->getAll('SELECT id, name, lastname FROM customers') as $row) {
                $this->owners[(int) $row['id']] = [
                    'id' => (int) $row['id'],
                    'name' => $row['name'],
                    'secondName' => $row['lastname'],
                ];
            }
        }

        return $this->owners[$customerId] ?? [];
    }
}
