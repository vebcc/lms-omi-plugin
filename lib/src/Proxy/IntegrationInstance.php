<?php

declare(strict_types=1);

namespace Omi\Proxy;

use Omi\Support\Strings;

/**
 * Doklejanie wskazania instancji integracji (integrationSlug / integrationInstanceId)
 * do zapytań read OltManagera, które dopasowują ONU po lms_id.
 *
 * Od czasu wielu instancji integracji ten sam lms_id może istnieć w kilku LMS-ach -
 * bez parametru OltManager zwróciłby sumę trafień ze wszystkich instancji.
 */
final class IntegrationInstance
{
    /** Endpointy OltManagera honorujące parametr instancji. */
    private const HONORING_PATHS = ['api/v1/onu', 'onu/lms/get/onus'];

    public static function honors(string $endpoint): bool
    {
        $path = strtolower(explode('?', $endpoint, 2)[0]);

        return in_array(trim($path, '/'), self::HONORING_PATHS, true);
    }

    /**
     * @param string $param np. "integrationSlug=lmsv2" (pusty = bez zmian)
     */
    public static function apply(string $endpoint, string $param): string
    {
        if ($param === '' || !self::honors($endpoint) || preg_match('/[?&]integration(Slug|InstanceId)=/i', $endpoint)) {
            return $endpoint;
        }

        return Strings::appendQuery($endpoint, $param);
    }
}
