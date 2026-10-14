<?php

declare(strict_types=1);

namespace Omi\Lms;

/**
 * Wąski interfejs do metod obiektu LMS, z których korzysta plugin.
 * Wyniki są zwracane w oryginalnym kształcie LMS (łącznie z kluczami meta typu "total").
 */
interface LmsApi
{
    /** @return array<mixed> */
    public function getNodeList(): array;

    /** @return array<mixed> */
    public function getCustomerList(): array;

    /** @return array<mixed> */
    public function getTariffs(): array;

    /** @return array<mixed> */
    public function getNetNodeList(): array;

    /** @return array<string, mixed>|null */
    public function getNode(int $id): ?array;

    public function getNodeIdByMac(string $mac): ?int;
}
