<?php

declare(strict_types=1);

namespace Omi\Lms;

/**
 * Minimalny interfejs bazy danych używany przez plugin (adapter LMSDB).
 * Zapytania muszą być przenośne (MySQL + PostgreSQL) i parametryzowane.
 */
interface Database
{
    /**
     * @param list<mixed> $params
     * @return list<array<string, mixed>>
     */
    public function getAll(string $sql, array $params = []): array;

    /**
     * @param list<mixed> $params
     * @return array<string, mixed>|null
     */
    public function getRow(string $sql, array $params = []): ?array;

    /**
     * @param list<mixed> $params
     * @return mixed
     */
    public function getOne(string $sql, array $params = []);

    public function tableExists(string $table): bool;
}
