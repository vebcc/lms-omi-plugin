<?php

/**
 * Akcje ONU w OltManagerze (np. restart): wyłącznie POST z tokenem CSRF w nagłówku
 * X-OMI-CSRF-Token. Uprawnienie: omi_full_access.
 */

$omiServices = LMSOmiPlugin::services();
$omiCsrf = $omiServices->csrf();
$omiToken = $_SERVER[\Omi\Security\CsrfTokenManager::SERVER_KEY] ?? ($_POST['csrf_token'] ?? null);

$omiServices->proxy()
    ->action(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_POST, $omiCsrf->isValid($omiToken))
    ->send();
die;
