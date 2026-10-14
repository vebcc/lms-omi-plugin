<?php

declare(strict_types=1);

namespace Omi\Repository;

use Omi\Lms\Database;

/**
 * Konfiguracja dostępu komputerów (taryfa internetowa, prędkości, saldo) dla OltManagera.
 *
 * Wersja przenośna (MySQL + PostgreSQL): agregaty liczbowe liczy SQL, a listy
 * (MAC-i, grup, taryf) są składane w PHP z osobnych, posortowanych zapytań - zamiast
 * GROUP_CONCAT ... SEPARATOR, którego PostgreSQL nie zna. Pola i ich kolejność są takie
 * same jak w legacy.
 */
class NodeAccessConfigurationRepository
{
    private const TARIFF_TYPE_INTERNET = 1;

    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param list<int> $ignoreGroups ID grup komputerów do pominięcia
     * @param int|null $at dzień naliczania zobowiązania (assignments.at), null/0 = bez filtra
     * @param int|null $today znacznik czasu początku bieżącego dnia (domyślnie północ wg strefy PHP)
     * @return list<array<string, mixed>>
     */
    public function findAll(array $ignoreGroups = [], ?int $at = null, ?int $today = null): array
    {
        $today = $today ?? (int) strtotime('today');
        [$filterSql, $filterParams] = $this->assignmentFilter($ignoreGroups, $at, $today);

        $rows = $this->db->getAll(
            'SELECT n.id AS node_id,
                n.name AS node_name,
                INET_NTOA(n.ipaddr) AS ip_private,
                INET_NTOA(n.ipaddr_pub) AS ip_public,
                n.access,
                n.warning,
                n.chkmac,
                n.halfduplex,
                COUNT(DISTINCT t.id) AS tariff_count,
                MAX(t.downceil) AS downceil,
                MAX(t.downceil_n) AS downceil_n,
                MAX(t.upceil) AS upceil,
                MAX(t.upceil_n) AS upceil_n,
                MAX(cb.balance) AS customer_balance,
                MAX(
                    ROUND(
                        t.value * (1 - COALESCE(a.pdiscount, 0) / 100) - COALESCE(a.vdiscount, 0),
                        2
                    )
                ) AS calculated_tariff_price
            ' . $filterSql . '
            GROUP BY n.id, n.name, n.ipaddr, n.ipaddr_pub, n.access, n.warning, n.chkmac, n.halfduplex
            ORDER BY n.id',
            $filterParams
        );

        if ($rows === []) {
            return [];
        }

        $tariffIds = $this->groupedColumn(
            'SELECT DISTINCT n.id AS node_id, t.id AS value ' . $filterSql . ' ORDER BY n.id, t.id',
            $filterParams
        );
        $groupIds = $this->groupedColumn(
            'SELECT DISTINCT nga.nodeid AS node_id, ng.id AS value
            FROM nodegroupassignments nga
            JOIN nodegroups ng ON ng.id = nga.nodegroupid
            ORDER BY nga.nodeid, ng.id'
        );
        $macs = $this->groupedColumn(
            'SELECT DISTINCT m.nodeid AS node_id, m.mac AS value
            FROM macs m
            WHERE m.mac IS NOT NULL
            ORDER BY m.nodeid, m.mac'
        );

        $result = [];
        foreach ($rows as $row) {
            $nodeId = (int) $row['node_id'];
            $nodeMacs = $macs[$nodeId] ?? [];
            sort($nodeMacs, SORT_STRING);

            $result[] = [
                'node_id' => $row['node_id'],
                'node_name' => $row['node_name'],
                'ip_private' => $row['ip_private'],
                'ip_public' => $row['ip_public'],
                'node_macs' => self::implodeOrNull($nodeMacs),
                'access' => $row['access'],
                'warning' => $row['warning'],
                'chkmac' => $row['chkmac'],
                'group_ids' => self::implodeOrNull($groupIds[$nodeId] ?? []),
                'halfduplex' => $row['halfduplex'],
                'tariff_ids' => self::implodeOrNull($tariffIds[$nodeId] ?? []),
                'tariff_count' => $row['tariff_count'],
                'downceil' => $row['downceil'],
                'downceil_n' => $row['downceil_n'],
                'upceil' => $row['upceil'],
                'upceil_n' => $row['upceil_n'],
                'customer_balance' => $row['customer_balance'],
                'calculated_tariff_price' => $row['calculated_tariff_price'],
            ];
        }

        return $result;
    }

    /**
     * Wspólne FROM/WHERE dla zapytania głównego i listy taryf.
     *
     * @param list<int> $ignoreGroups
     * @return array{0: string, 1: list<int>}
     */
    private function assignmentFilter(array $ignoreGroups, ?int $at, int $today): array
    {
        $sql = '
            FROM nodes n
            JOIN nodeassignments na ON na.nodeid = n.id
            JOIN assignments a ON a.id = na.assignmentid
            JOIN tariffs t ON t.id = a.tariffid
            LEFT JOIN customerbalances cb ON cb.customerid = n.ownerid
            WHERE n.ownerid > 0
                AND a.suspended = 0
                AND (a.dateto > ? OR a.dateto = 0)
                AND (a.datefrom < ? OR a.datefrom = 0)
                AND t.type = ?';
        $params = [$today, $today, self::TARIFF_TYPE_INTERNET];

        $ignoreGroups = array_values(array_filter(array_map('intval', $ignoreGroups), static function (int $id): bool {
            return $id > 0;
        }));
        if ($ignoreGroups !== []) {
            $sql .= ' AND NOT EXISTS (SELECT 1 FROM nodegroupassignments nga2 WHERE nga2.nodeid = n.id AND nga2.nodegroupid IN ('
                . implode(',', array_fill(0, count($ignoreGroups), '?')) . '))';
            $params = array_merge($params, $ignoreGroups);
        }

        if ($at !== null && $at !== 0) {
            $sql .= ' AND a.at = ?';
            $params[] = $at;
        }

        return [$sql, $params];
    }

    /**
     * @param list<mixed> $params
     * @return array<int, list<string>> node_id => wartości w kolejności zapytania
     */
    private function groupedColumn(string $sql, array $params = []): array
    {
        $grouped = [];
        foreach ($this->db->getAll($sql, $params) as $row) {
            if ($row['value'] === null) {
                continue;
            }
            $grouped[(int) $row['node_id']][] = (string) $row['value'];
        }

        return $grouped;
    }

    /**
     * @param list<string> $values
     */
    private static function implodeOrNull(array $values): ?string
    {
        return $values === [] ? null : implode(',', $values);
    }
}
