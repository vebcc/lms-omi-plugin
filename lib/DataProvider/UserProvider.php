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

    public function getUserToken(int $id): ?string
    {
        $hash = $this->repository->getUserPasswordHash($id);
        $login = $this->repository->getUserLogin($id);
        return $login."$".md5($hash);
    }

    public function getUserLogin(int $id): ?string
    {
        return $this->repository->getUserLogin($id);
    }

    public function getUserTokenCollection(): array
    {
        $userPasswordHashCollection = $this->repository->getUserPasswordHashCollection();
        $userTokenCollection = [];

        foreach ($userPasswordHashCollection as $userPasswordHash){
            $userTokenCollection[$userPasswordHash['login']] = $userPasswordHash["login"]."$".md5($userPasswordHash['passwd']);
        }
        return $userTokenCollection;
    }
}