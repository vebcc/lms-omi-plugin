<?php

declare(strict_types=1);

namespace Omi\Repository;

use Omi\Lms\Database;

/**
 * Login (nazwa) i hasło komputerów - jedno zapytanie zamiast GetNode() per komputer.
 */
class NodeCredentials
{
    private Database $db;

    /** @var array<int, array{name: mixed, passwd: mixed}>|null */
    private ?array $nodes = null;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @return array{name: mixed, passwd: mixed}|null
     */
    public function forNode(int $nodeId): ?array
    {
        if ($this->nodes === null) {
            $this->nodes = [];
            foreach ($this->db->getAll('SELECT id, name, passwd FROM nodes') as $row) {
                $this->nodes[(int) $row['id']] = ['name' => $row['name'], 'passwd' => $row['passwd']];
            }
        }

        return $this->nodes[$nodeId] ?? null;
    }
}
