<?php

require_once 'Repository/UserRepository.php';

class UserProvider
{
    private $lms;
    private $repository;

    private $hasAlgorithm = PASSWORD_BCRYPT;

    public function __construct()
    {
        $this->lms = LMS::getInstance();
        $this->repository = new UserRepository();

    }

    public function getUserToken(int $id): ?string
    {
        return password_hash($this->repository->getUserPasswordHash($id), $this->hasAlgorithm);
    }

    public function getUserTokenCollection(): array
    {
        $userPasswordHashCollection = $this->repository->getUserPasswordHashCollection();
        $userTokenCollection = [];

        foreach ($userPasswordHashCollection as $userPasswordHash){
            $userTokenCollection[$userPasswordHash['login']] = password_hash($userPasswordHash['passwd'], $this->hasAlgorithm);
        }
        return $userTokenCollection;
    }
}