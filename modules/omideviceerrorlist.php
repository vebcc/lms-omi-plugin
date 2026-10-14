<?php

/**
 * Lista urządzeń z błędnym adresem ONU w opisie. Moduł jest domyślnie wyłączony
 * (omi.device_error_list_enabled = false) - wtedy zachowuje się jak nieistniejący moduł.
 */

$omiServices = LMSOmiPlugin::services();
$omiConfig = $omiServices->config();

if (!$omiConfig->deviceErrorListEnabled()) {
    $layout['module'] = 'notfound';
    $layout['pagetitle'] = trans('Error!');
    $SMARTY->assign('layout', $layout);
    $SMARTY->assign('server', $_SERVER);
    $SMARTY->display('notfound.html');

    return;
}

$layout['pagetitle'] = trans('Device with error list');

$errorDevices = [];
if ($omiConfig->providerType() === 'description') {
    $errorDevices = (new \Omi\Device\ErrorDeviceList($omiServices->lms()))->build($omiServices->deviceLists()->connectionsWithError());
}

$pagelimit = $omiConfig->deviceErrorListPageLimit();
$total = count($errorDevices);
$pages = max(1, (int) ceil($total / $pagelimit));
$page = isset($_GET['page']) && is_string($_GET['page']) && ctype_digit($_GET['page']) ? (int) $_GET['page'] : 1;
$page = min(max(1, $page), $pages);
$start = ($page - 1) * $pagelimit;

$SMARTY->assign('page', $page);
$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('start', $start);
$SMARTY->assign('listdata', ['total' => $total]);
$SMARTY->assign('errorDevices', $errorDevices);
$SMARTY->display('omideviceerrorlist.html');
