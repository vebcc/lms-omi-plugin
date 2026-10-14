<?php

declare(strict_types=1);

namespace Omi\Security;

interface SessionStore
{
    /**
     * @return mixed
     */
    public function get(string $name);

    /**
     * @param mixed $value
     */
    public function save(string $name, $value): void;
}
