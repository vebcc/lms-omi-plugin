<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once(PLUG_DIR.'/LMSOmiPlugin/lib/OMI.php');

$omi = new OMI();

$layout['pagetitle'] = 'OMI - API';

$params = $_GET;

$module = isset($params['module']) ? $params['module'] : 'omi';

$type = isset($params['type']) ? $params['type'] : 'error';

unset($params['m'] ,$params['module'], $params['type']);

$data = $omi->getFromOmiModule($type, $params);

header('Content-Type: application/json');
echo json_encode($data);
//echo json_encode(array_values($data));
die;