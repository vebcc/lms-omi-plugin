<?php


class CustomerRepository
{
    private $db;

    public function __construct()
    {
        $this->db = LMSDB::getInstance();
    }

    public function findCustomerById(int $id): array
    {
        return $this->db->GetRow(
            'SELECT * FROM customers WHERE id=?',
            array($id)
        );

    }
}