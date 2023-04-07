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

    public function getAddressByAddressId(int $address_id): array
    {
        return $this->repository->findAddressByAddressId($address_id);
    }
}