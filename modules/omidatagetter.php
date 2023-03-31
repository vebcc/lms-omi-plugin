<?php

$omi = LMSOmiPlugin::getOMIInstance();

$layout['pagetitle'] = 'OMI - API';

$params = $_GET;

$module = $params['module'] ?? 'omi';

$type = $params['type'] ?? 'error';

unset($params['m'] ,$params['module'], $params['type']);

$data = $omi->getFromOmiModule($type, $params);

header('Content-Type: application/json');
echo json_encode($data);
//echo json_encode(array_values($data));
die;