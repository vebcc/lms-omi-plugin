<?php

require_once 'Repository/AddressRepository.php';

class AddressProvider
{
    private $repository;

    public function __construct()
    {
        $this->repository = new AddressRepository();

    }

    public function getAddressByAddressId($address_id)
    {
        return $this->repository->findAddressByAddressId($address_id);
    }
}