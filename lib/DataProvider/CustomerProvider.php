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

    public function getCustomerById(int $id): array
    {
        return $this->repository->findCustomerById($id);
    }
}