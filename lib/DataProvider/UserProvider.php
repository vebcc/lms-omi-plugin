<?php

require_once 'Repository/UserRepository.php';

class UserProvider
{
    private $lms;
    private $repository;

    public function __construct()
    {
        $this->lms = LMS::getInstance();
        $this->repository = new UserRepository();

    }

    public function getUserPasswordHash(int $id): ?string
    {
        return $this->repository->getUserPasswordHash($id);
    }
}