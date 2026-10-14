<?php

/**
 * Przekierowanie do OltManagera: ?m=omiredirect[&url=<ścieżka względna>]
 * Przy włączonym omi.olt_manager_automatic_login loguje użytkownika przez SSO.
 */

$omiServices = LMSOmiPlugin::services();
$omiUrl = isset($_GET['url']) && is_string($_GET['url']) ? $_GET['url'] : '';

if ($omiServices->config()->oltManagerUrl() === '') {
    \Omi\Proxy\ProxyResponse::error(503, 'OltManager not configured (omi.olt_manager_url)')->send();
    die;
}

\Omi\Proxy\ProxyResponse::redirect($omiServices->ssoRedirector()->redirectUrl($omiUrl, $omiServices->currentUser()))->send();
die;
