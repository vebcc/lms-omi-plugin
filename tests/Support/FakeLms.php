<?php

declare(strict_types=1);

namespace Omi\Tests\Support;

use Omi\Lms\LmsApi;

final class FakeLms implements LmsApi
{
    /** @var array<mixed> */
    public array $nodeList = [];
    /** @var array<mixed> */
    public array $customerList = [];
    /** @var array<mixed> */
    public array $tariffs = [];
    /** @var array<mixed> */
    public array $netNodes = [];
    /** @var array<int, array<string, mixed>> */
    public array $nodes = [];
    /** @var array<string, int> */
    public array $macs = [];

    public function getNodeList(): array
    {
        return $this->nodeList;
    }

    public function getCustomerList(): array
    {
        return $this->customerList;
    }

    public function getTariffs(): array
    {
        return $this->tariffs;
    }

    public function getNetNodeList(): array
    {
        return $this->netNodes;
    }

    public function getNode(int $id): ?array
    {
        return $this->nodes[$id] ?? null;
    }

    public function getNodeIdByMac(string $mac): ?int
    {
        return $this->macs[$mac] ?? null;
    }

    public function getNodeName(int $id): string
    {
        return (string) ($this->nodes[$id]['name'] ?? '');
    }

    public function getNetDevName(int $id): string
    {
        return 'netdev' . $id;
    }
}
