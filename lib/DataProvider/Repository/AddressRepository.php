<?php


class AddressRepository
{
    private $db;

    public function __construct()
    {
        $this->db = LMSDB::getInstance();
    }

    public function findAddressByAddressId($address_id)
    {
        return $this->db->GetRow(
            'SELECT
                a.house as house,
                lst.ident as streetIdent,
                lc.ident as cityIdent
            FROM vaddresses a
            LEFT JOIN location_cities lc ON lc.id = a.city_id
            LEFT JOIN location_streets lst ON lst.id = a.street_id
            WHERE a.id = ?',
            array($address_id)
        );

    }

}