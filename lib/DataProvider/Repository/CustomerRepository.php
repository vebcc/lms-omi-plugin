<?php


class CustomerRepository
{

    public function findCustomerById($id)
    {
        global $LMS, $DB;
        return $DB->GetRow(
            'SELECT * FROM customers WHERE id=?',
            array($id)
        );

    }
}