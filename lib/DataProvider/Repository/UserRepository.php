<?php

class UserRepository
{
    private $db;

    public function __construct()
    {
        $this->db = LMSDB::getInstance();
    }

    public function getUserPasswordHash(int $id): ?string
    {
        return $this->db->GetOne('SELECT passwd FROM vusers WHERE id=?', array($id));
    }
}