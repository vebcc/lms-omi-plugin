<?php

declare(strict_types=1);

namespace Omi\Collection;

use Omi\Lms\LmsApi;

final class TariffCollection implements CollectionProvider
{
    private LmsApi $lms;

    public function __construct(LmsApi $lms)
    {
        $this->lms = $lms;
    }

    public function name(): string
    {
        return 'tariffs';
    }

    public function fetchRaw(): array
    {
        return $this->lms->getTariffs();
    }
}
