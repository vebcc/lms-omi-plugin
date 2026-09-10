<?php

$omi = LMSOmiPlugin::getOMIInstance();

$layout['pagetitle'] = 'OMI - API';

$params = $_GET;

$module = $params['module'] ?? 'omi';

$type = $params['type'] ?? 'error';

unset($params['m'] ,$params['module'], $params['type']);

// Fatale niełapane przez try/catch (limit czasu / pamieci) i tak wyladuja tutaj w logu.
register_shutdown_function(static function () use ($type) {
    $err = error_get_last();
    if ($err && ($err['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR))) {
        error_log(sprintf('[omidatagetter] FATAL type=%s: %s @ %s:%d', $type, $err['message'], $err['file'], $err['line']));
    }
});

try {
    $data = $omi->getFromOmiModule($type, $params);
} catch (\Throwable $e) {
    error_log(sprintf(
        '[omidatagetter] type=%s %s: %s @ %s:%d',
        $type,
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));
    http_response_code(500);
    $data = [
        'exception' => $e->getMessage(),
        'class' => get_class($e),
        'at' => $e->getFile() . ':' . $e->getLine(),
        'code' => 500,
    ];
}

header('Content-Type: application/json');
echo json_encode($data);
//echo json_encode(array_values($data));
die;