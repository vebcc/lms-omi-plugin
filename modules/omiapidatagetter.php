<?php

if(LMSOmiPlugin::PLUGIN_MODE === 'DEV'){
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

$omi = LMSOmiPlugin::getOMIInstance();

$layout['pagetitle'] = 'OMI - API';

$params = $_GET;

$type = $params['type'] ?? 'error';

unset($params['m'] ,$params['module'], $params['type']);

$data = $omi->getFromApiModule($type, $params);

header('Content-Type: application/json');
echo json_encode($data);
//echo json_encode(array_values($data));
die;