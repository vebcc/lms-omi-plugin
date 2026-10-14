<?php

declare(strict_types=1);

namespace Omi\Api;

interface Action
{
    public function name(): string;

    /**
     * @param array<string, mixed> $params parametry z query stringu
     * @return mixed dane trafiające do pola "data"
     * @throws ApiException
     */
    public function handle(array $params);
}
