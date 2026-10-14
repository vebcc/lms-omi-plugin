<?php

declare(strict_types=1);

namespace Omi\Lms;

/**
 * Adapter LMSDB (LMSDB_common).
 */
final class LmsDatabase implements Database
{
    /** @var \LMSDB_common */
    private $db;

    /**
     * @param \LMSDB_common $db instancja LMSDB (LMSDB::getInstance())
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll(string $sql, array $params = []): array
    {
        $rows = $this->db->GetAll($sql, $params === [] ? null : $params);

        return is_array($rows) ? array_values($rows) : [];
    }

    public function getRow(string $sql, array $params = []): ?array
    {
        $row = $this->db->GetRow($sql, $params === [] ? null : $params);

        return is_array($row) && $row !== [] ? $row : null;
    }

    public function getOne(string $sql, array $params = [])
    {
        return $this->db->GetOne($sql, $params === [] ? null : $params);
    }

    public function tableExists(string $table): bool
    {
        if (method_exists($this->db, 'ResourceExists') && defined('LMSDB::RESOURCE_TYPE_TABLE')) {
            return (bool) $this->db->ResourceExists($table, constant('LMSDB::RESOURCE_TYPE_TABLE'));
        }

        return (int) $this->db->GetOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_name = ?',
            [$table]
        ) > 0;
    }
}
