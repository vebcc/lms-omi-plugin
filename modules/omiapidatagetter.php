<?php

/**
 * @deprecated Stare API kolekcji (?m=omiapidatagetter&type=...) dla OltManagerów bez API v3.
 * Kształt odpowiedzi bez zmian względem pluginu 2.x. Nowe integracje: ?m=omiapi.
 */

$omiType = isset($_GET['type']) && is_string($_GET['type']) ? $_GET['type'] : 'error';

\Omi\Legacy\LegacyResponder::run('omiapidatagetter', $omiType, static function () use ($omiType) {
    return LMSOmiPlugin::services()->legacyApiDataGetter()->handle($omiType);
});
