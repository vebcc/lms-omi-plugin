<?php

declare(strict_types=1);

namespace Omi\Repository;

use Omi\Lms\Database;

/**
 * Właściciele komputerów podłączonych do urządzeń sieciowych (odpowiednik
 * GetNetDevLinkedNodes() per urządzenie, w kolejności nazw komputerów) - jedno zapytanie.
 */
class NetDevLinkedOwners
{
    private Database $db;

    /** @var array<int, list<int>>|null netdev_id => lista ownerid */
    private ?array $owners = null;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @return list<int> ID właścicieli kolejnych komputerów (puste = brak podłączonych komputerów)
     */
    public function ownersOf(int $netDevId): array
    {
        if ($this->owners === null) {
            $this->owners = [];
            $rows = $this->db->getAll(
                'SELECT n.netdev, n.ownerid
                FROM vnodes n
                JOIN customerview c ON c.id = n.ownerid
                JOIN networks net ON net.id = n.netid
                WHERE n.netdev IS NOT NULL AND n.ownerid IS NOT NULL
                ORDER BY n.netdev, n.name, n.id'
            );
            foreach ($rows as $row) {
                $this->owners[(int) $row['netdev']][] = (int) $row['ownerid'];
            }
        }

        return $this->owners[$netDevId] ?? [];
    }
}
