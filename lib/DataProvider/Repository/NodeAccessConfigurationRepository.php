<?php

class NodeAccessConfigurationRepository
{
    private $db;

    public function __construct()
    {
        $this->db = LMSDB::getInstance();
    }

    public function findNodeAccessConfigurationCollection($ignoreGroups = [], $at = null): ?array
    {
        $query = "SELECT 
            n.id AS node_id,
            n.name AS node_name,
            INET_NTOA(n.ipaddr) AS ip_private,
            INET_NTOA(n.ipaddr_pub) AS ip_public,
            GROUP_CONCAT(DISTINCT m.mac ORDER BY m.mac SEPARATOR ',') AS node_macs,
            n.access,
            n.warning,
            n.chkmac,
            GROUP_CONCAT(DISTINCT ng.id ORDER BY ng.id SEPARATOR ',') AS group_ids,
            n.halfduplex,
            GROUP_CONCAT(DISTINCT t.id ORDER BY t.id SEPARATOR ',') AS tariff_ids,
            COUNT(DISTINCT t.id) AS tariff_count,
            MAX(t.downceil) AS downceil,
            MAX(t.downceil_n) AS downceil_n,
            MAX(t.upceil) AS upceil,
            MAX(t.upceil_n) AS upceil_n,
            cb.balance AS customer_balance,
            MAX(
                ROUND(
                    t.value * (1 - COALESCE(a.pdiscount, 0) / 100) - COALESCE(a.vdiscount, 0),
                    2
                )
            ) AS calculated_tariff_price
        
        FROM 
            nodes n
        LEFT JOIN macs m ON m.nodeid = n.id
        LEFT JOIN nodegroupassignments nga ON nga.nodeid = n.id
        LEFT JOIN nodegroups ng ON ng.id = nga.nodegroupid
        LEFT JOIN nodeassignments na ON na.nodeid = n.id
        LEFT JOIN assignments a ON a.id = na.assignmentid
        LEFT JOIN tariffs t ON t.id = a.tariffid
        LEFT JOIN customerbalances cb ON cb.customerid = n.ownerid
        WHERE 
            n.ownerid > 0
            AND a.suspended = 0
            AND (a.dateto > UNIX_TIMESTAMP(CURDATE()) OR a.dateto = 0)
            AND (a.datefrom < UNIX_TIMESTAMP(CURDATE()) OR a.datefrom = 0)
            AND t.type = 1
            " . (count($ignoreGroups) > 0 ? " AND NOT EXISTS (SELECT 1 FROM nodegroupassignments nga2 WHERE nga2.nodeid = n.id AND nga2.nodegroupid IN (" . implode(",", $ignoreGroups) . "))" : "") . "
            " . ($at ? "AND a.at = " . $at : "") . "
        GROUP BY 
            n.id";

        return $this->db->GetAll($query);
    }

}