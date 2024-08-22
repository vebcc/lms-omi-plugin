<?php

require_once 'Repository/UserRepository.php';

class UserProvider
{
    private $repository;
    public function __construct()
    {
        $this->repository = new UserRepository();
    }

    public function getUserToken($id)
    {
        $hash = $this->repository->getUserPasswordHash($id);
        $login = $this->repository->getUserLogin($id);
        return $login."$".md5($hash);
    }

    public function getUserLogin($id)
    {
        return $this->repository->getUserLogin($id);
    }

    public function getUserTokenCollection()
    {
        $userPasswordHashCollection = $this->repository->getUserPasswordHashCollection();
        $userTokenCollection = [];

        foreach ($userPasswordHashCollection as $userPasswordHash){
            $userTokenCollection[$userPasswordHash['login']] = $userPasswordHash["login"]."$".md5($userPasswordHash['passwd']);
        }
        return $userTokenCollection;
    }
}