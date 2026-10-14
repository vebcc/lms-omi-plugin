<?php

/**
 * API v3 dla OltManagera: ?m=omiapi&action=<akcja> (docs/API_V3.md).
 * Uprawnienie: omi_api_user (albo omi_full_access).
 */

$omiAction = isset($_GET['action']) && is_string($_GET['action']) ? $_GET['action'] : '';
$omiParams = $_GET;
unset($omiParams['m'], $omiParams['action']);

LMSOmiPlugin::services()->apiRouter()->dispatch($omiAction, $omiParams)->send();
die;
