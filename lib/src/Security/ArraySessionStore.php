<?php

declare(strict_types=1);

namespace Omi\Security;

final class ArraySessionStore implements SessionStore
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function save(string $name, $value): void
    {
        $this->data[$name] = $value;
    }
}
