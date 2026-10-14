<?php

declare(strict_types=1);

namespace Omi\Repository;

use Omi\Lms\Database;

class NodeGroupRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @return array<int, array<string, mixed>> grupy indeksowane po id (jak GetAllByKey)
     */
    public function findAll(): array
    {
        $groups = [];
        foreach ($this->db->getAll('SELECT id, name, description FROM nodegroups ORDER BY id') as $row) {
            $groups[(int) $row['id']] = $row;
        }

        return $groups;
    }
}
