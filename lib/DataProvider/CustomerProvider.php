<?php

require_once 'Repository/CustomerRepository.php';

class CustomerProvider
{
    private $repository;

    public function __construct()
    {
        $this->repository = new CustomerRepository();

    }

    public function getCustomerById($id)
    {
        return $this->repository->findCustomerById($id);
    }
}