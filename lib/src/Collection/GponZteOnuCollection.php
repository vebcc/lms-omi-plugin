<?php

declare(strict_types=1);

namespace Omi\Collection;

use Omi\Api\ApiException;
use Omi\Repository\GponZteRepository;

final class GponZteOnuCollection implements CollectionProvider
{
    private GponZteRepository $repository;

    public function __construct(GponZteRepository $repository)
    {
        $this->repository = $repository;
    }

    public function name(): string
    {
        return 'gponZteOnus';
    }

    public function fetchRaw(): array
    {
        if (!$this->repository->isAvailable()) {
            throw ApiException::featureDisabled('LMS GPON ZTE plugin tables are not available');
        }

        return $this->repository->findOnus();
    }
}
