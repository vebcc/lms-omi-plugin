<?php

/**
 * Proxy odczytu do OltManagera (tylko GET): ?m=omiapiproxy&endpoint=<ścieżka względna>[&redirect=1]
 * Token OltManagera zostaje na serwerze. Akcje zmieniające stan: ?m=omiapiaction (POST + CSRF).
 */

$omiServices = LMSOmiPlugin::services();
$omiServices->proxy()
    ->read(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_GET, $omiServices->currentUser())
    ->send();
die;
