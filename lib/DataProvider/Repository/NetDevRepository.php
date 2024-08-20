<?php


class NetDevRepository
{
    private $db;

    public function __construct()
    {
        $this->db = LMSDB::getInstance();
    }

    public function findNetDevCollection()
    {
        return $this->db->GetAll('SELECT d.id, d.name,
				d.description, d.producer, d.model, m.type AS devtype, t.name AS devtypename,
				d.serialnumber, d.ports, d.ownerid, d.longitude, d.latitude, 
				d.invprojectid, p.name AS project, d.status,
				(SELECT COUNT(*) FROM nodes WHERE ipaddr <> 0 AND netdev=d.id AND ownerid IS NOT NULL)
				+ (SELECT COUNT(*) FROM netlinks WHERE src = d.id OR dst = d.id)
				AS takenports, d.netnodeid, n.name AS netnode,
				lb.name AS borough_name, lb.type AS borough_type, lb.ident AS borough_ident,
				ld.name AS district_name, ld.ident AS district_ident,
				ls.name AS state_name, ls.ident AS state_ident,
				addr.state as location_state_name, addr.state_id as location_state,
				addr.zip as location_zip, addr.country_id as location_country,
				addr.city as location_city_name, addr.city_id as location_city,
				lc.ident AS city_ident,
				addr.street AS location_street_name, addr.street_id as location_street,
				(CASE WHEN lst.ident IS NULL
					THEN (CASE WHEN addr.street = \'\' THEN \'99999\' ELSE \'99998\' END)
					ELSE lst.ident END) AS street_ident,
				addr.house as location_house, addr.flat as location_flat, addr.location, no.lastonline
			FROM netdevices d 
			    LEFT JOIN (
			        SELECT netdev AS netdevid, MAX(lastonline) AS lastonline
			        FROM nodes
			        WHERE nodes.netdev IS NOT NULL AND nodes.ownerid IS NULL
			            AND lastonline > 0 
			        GROUP BY netdev
			    ) no ON no.netdevid = d.id 
				LEFT JOIN vaddresses addr       ON d.address_id = addr.id
				LEFT JOIN invprojects p         ON p.id = d.invprojectid
				LEFT JOIN netnodes n            ON n.id = d.netnodeid
				LEFT JOIN netdevicemodels m     ON m.id = d.netdevicemodelid
				LEFT JOIN netdevicetypes t      ON t.id = m.type
				LEFT JOIN location_streets lst  ON lst.id = addr.street_id
				LEFT JOIN location_cities lc    ON lc.id = addr.city_id
				LEFT JOIN location_boroughs lb  ON lb.id = lc.boroughid
				LEFT JOIN location_districts ld ON ld.id = lb.districtid
				LEFT JOIN location_states ls    ON ls.id = ld.stateid '
        );
    }
}