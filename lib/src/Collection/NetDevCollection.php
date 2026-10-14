<?php

declare(strict_types=1);

namespace Omi\Collection;

use Omi\Repository\NetDevLocationResolver;
use Omi\Repository\NetDevRepository;

/**
 * Urządzenia sieciowe + "locationAddress" (adres TERYT urządzenia albo właściciela).
 */
final class NetDevCollection implements CollectionProvider, HasVolatileFields
{
    private NetDevRepository $repository;
    private NetDevLocationResolver $locationResolver;

    public function __construct(NetDevRepository $repository, NetDevLocationResolver $locationResolver)
    {
        $this->repository = $repository;
        $this->locationResolver = $locationResolver;
    }

    public function name(): string
    {
        return 'netdevices';
    }

    public function fetchRaw(): array
    {
        $netDevs = $this->repository->findAll();
        foreach ($netDevs as $key => $netDev) {
            $netDevs[$key]['locationAddress'] = $this->locationResolver->resolve($netDev)['locationAddress'];
        }

        return $netDevs;
    }

    public function volatileFields(): array
    {
        return ['lastonlinedate'];
    }
}
