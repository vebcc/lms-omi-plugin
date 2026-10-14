<?php

declare(strict_types=1);

namespace Omi\Repository;

use Omi\Lms\Database;

/**
 * ONU z pluginu LMS GPON ZTE (tabele gpononu*). Plugin GPON ZTE jest opcjonalny.
 */
class GponZteRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function isAvailable(): bool
    {
        return $this->db->tableExists('gpononu')
            && $this->db->tableExists('gpononu2olt')
            && $this->db->tableExists('gpononu2customers');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findOnus(): array
    {
        return $this->db->getAll(
            'SELECT gpononu.id, gpononu.serialnumber, gpononu.nodeid, gpononu.onuid,
                gpononu2olt.netdevid, gpononu2olt.numport, gpononu2customers.customerid
            FROM gpononu
            LEFT JOIN gpononu2olt ON gpononu2olt.gpononuid = gpononu.id
            LEFT JOIN gpononu2customers ON gpononu2customers.gpononuid = gpononu.id
            ORDER BY gpononu.id, gpononu2olt.netdevid, gpononu2customers.customerid'
        );
    }
}
