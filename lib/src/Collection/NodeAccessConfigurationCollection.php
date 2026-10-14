<?php

declare(strict_types=1);

namespace Omi\Collection;

use Omi\Config\OmiConfig;
use Omi\Repository\NodeAccessConfigurationRepository;

final class NodeAccessConfigurationCollection implements CollectionProvider
{
    private NodeAccessConfigurationRepository $repository;
    private OmiConfig $config;

    public function __construct(NodeAccessConfigurationRepository $repository, OmiConfig $config)
    {
        $this->repository = $repository;
        $this->config = $config;
    }

    public function name(): string
    {
        return 'nodeAccessConfigurations';
    }

    public function fetchRaw(): array
    {
        return $this->repository->findAll(
            $this->config->nodeAccessConfigurationIgnoreGroups(),
            $this->config->nodeAccessConfigurationAt()
        );
    }
}
