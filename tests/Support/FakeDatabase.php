<?php

declare(strict_types=1);

namespace Omi\Tests\Support;

use Omi\Lms\Database;

/**
 * Baza testowa: odpowiedzi dopasowywane po fragmencie zapytania SQL.
 */
final class FakeDatabase implements Database
{
    /** @var array<string, list<array<string, mixed>>> */
    private array $results;
    /** @var list<array{sql: string, params: list<mixed>}> */
    public array $queries = [];
    /** @var array<string, bool> */
    private array $tables;

    /**
     * @param array<string, list<array<string, mixed>>> $results fragment SQL => wiersze
     * @param array<string, bool> $tables
     */
    public function __construct(array $results = [], array $tables = [])
    {
        $this->results = $results;
        $this->tables = $tables;
    }

    public function getAll(string $sql, array $params = []): array
    {
        $this->queries[] = ['sql' => $sql, 'params' => $params];
        foreach ($this->results as $fragment => $rows) {
            if (strpos($sql, $fragment) !== false) {
                return $rows;
            }
        }

        return [];
    }

    public function getRow(string $sql, array $params = []): ?array
    {
        $rows = $this->getAll($sql, $params);

        return $rows[0] ?? null;
    }

    public function getOne(string $sql, array $params = [])
    {
        $row = $this->getRow($sql, $params);

        return $row === null ? null : reset($row);
    }

    public function tableExists(string $table): bool
    {
        return $this->tables[$table] ?? false;
    }
}
