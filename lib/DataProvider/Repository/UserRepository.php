<?php

class UserRepository
{

    public function getUserPasswordHash($id)
    {
        global $LMS, $DB;
        return $DB->GetOne('SELECT passwd FROM users WHERE id=?', array($id));
    }

    public function getUserLogin($id)
    {
        global $LMS, $DB;
        return $DB->GetOne('SELECT login FROM users WHERE id=?', array($id));
    }

    public function getUserPasswordHashCollection()
    {
        global $LMS, $DB;
        return $DB->GetAll('SELECT login, passwd FROM users WHERE deleted=0');
    }
}