<?php

declare(strict_types=1);

namespace Omi\Collection;

use Omi\Repository\NodeGroupRepository;

final class NodeGroupCollection implements CollectionProvider
{
    private NodeGroupRepository $repository;

    public function __construct(NodeGroupRepository $repository)
    {
        $this->repository = $repository;
    }

    public function name(): string
    {
        return 'nodeGroups';
    }

    public function fetchRaw(): array
    {
        return $this->repository->findAll();
    }
}
