<?php

declare(strict_types=1);

namespace Omi\Sso;

final class CurrentUser
{
    private int $id;
    private string $login;

    public function __construct(int $id, string $login)
    {
        $this->id = $id;
        $this->login = $login;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function login(): string
    {
        return $this->login;
    }
}
