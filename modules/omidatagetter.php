<?php

if(LMSOmiPlugin::PLUGIN_MODE === 'DEV'){
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

$omi = LMSOmiPlugin::getOMIInstance();

$layout['pagetitle'] = 'OMI - API';

$params = $_GET;

$module = $params['module'] ?? 'omi';

$type = $params['type'] ?? 'error';

unset($params['m'] ,$params['module'], $params['type']);

switch ($module){
    case 'omi':
        $data = $omi->getFromOmiModule($type, $params);
        break;
    case 'api':
        $data = $omi->getFromApiModule($type, $params);
        break;
}

header('Content-Type: application/json');
echo json_encode($data);
//echo json_encode(array_values($data));
die;