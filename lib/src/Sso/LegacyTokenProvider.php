<?php

declare(strict_types=1);

namespace Omi\Sso;

/**
 * Źródło starego tokenu autologowania (login$md5(hash hasła)) - tylko dla trybu zgodności.
 */
interface LegacyTokenProvider
{
    public function tokenForUser(int $userId): ?string;
}
