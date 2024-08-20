<?php

require_once 'Repository/AddressRepository.php';

class AddressProvider
{
    private $lms;
    private $repository;

    public function __construct()
    {
        $this->lms = LMS::getInstance();
        $this->repository = new AddressRepository();

    }

    public function getAddressByAddressId($address_id)
    {
        return $this->repository->findAddressByAddressId($address_id);
    }
}