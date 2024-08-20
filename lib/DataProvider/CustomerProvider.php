<?php

require_once 'Repository/CustomerRepository.php';

class CustomerProvider
{
    private $lms;
    private $repository;

    public function __construct()
    {
        $this->lms = LMS::getInstance();
        $this->repository = new CustomerRepository();

    }

    public function getCustomerById($id)
    {
        return $this->repository->findCustomerById($id);
    }
}