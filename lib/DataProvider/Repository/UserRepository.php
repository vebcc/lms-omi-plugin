<?php

class UserRepository
{
    private $db;

    public function __construct()
    {
        $this->db = LMSDB::getInstance();
    }

    public function getUserPasswordHash($id)
    {
        return $this->db->GetOne('SELECT passwd FROM vusers WHERE id=?', array($id));
    }

    public function getUserLogin($id)
    {
        return $this->db->GetOne('SELECT login FROM vusers WHERE id=?', array($id));
    }

    public function getUserPasswordHashCollection()
    {
        return $this->db->GetAll('SELECT login, passwd FROM vusers WHERE deleted=0');
    }
}