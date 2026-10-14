<?php

/**
 * @deprecated Stare API pluginu (?m=omidatagetter&type=...) dla OltManagerów bez API v3.
 * Kształt odpowiedzi bez zmian względem pluginu 2.x. Nowe integracje: ?m=omiapi.
 */

$omiType = isset($_GET['type']) && is_string($_GET['type']) ? $_GET['type'] : 'error';
$omiParams = $_GET;
unset($omiParams['m'], $omiParams['module'], $omiParams['type']);

\Omi\Legacy\LegacyResponder::run('omidatagetter', $omiType, static function () use ($omiType, $omiParams) {
    return LMSOmiPlugin::services()->legacyOmiDataGetter()->handle($omiType, $omiParams);
});
