<?php

/**
 * omiapiproxy.php
 *
 * Server-side proxy do API OltManagera oraz stron WWW.
 * Zapytania do zewnetrznego API wychodza z serwera LMS - token nigdy nie trafia do przegladarki.
 *
 * Parametry GET:
 *   endpoint  - sciezka API/URL np. "api/v1/onu/?enabled=1&ownerLmsId=123" lub "onu/123"
 *   method    - GET (domyslnie) lub POST
 *   redirect  - "1" jesli endpoint ma byc przekierowany HTTP 302 do OltManagera
 */

$layout['pagetitle'] = 'OMI - API Proxy';

$omi = LMSOmiPlugin::getOmiInstance();

$oltManagerUrl = rtrim(ConfigHelper::getConfig('omi.olt_manager_url', ''), '/');
$token         = ConfigHelper::getConfig('omi.olt_manager_token', '');

// Opcjonalny bezpośredni IP – gdy DNS wskazuje na lokalny adres niedostępny z LMS
// lub gdy chcemy ominąć DNS w ogóle
$oltManagerDirectIp = ConfigHelper::getConfig('omi.olt_manager_ip', '');

$isAutomaticLoginEnabled = ConfigHelper::getConfig('omi.olt_manager_automatic_login', false);
$isAutomaticLoginEnabled = ($isAutomaticLoginEnabled === "true"
    || $isAutomaticLoginEnabled === true
    || $isAutomaticLoginEnabled == 1);

$sslVerify = ConfigHelper::getConfig('omi.ssl_verify', true);
$sslVerify = ($sslVerify === "true" || $sslVerify === true || $sslVerify == 1);

if (empty($oltManagerUrl) || empty($token)) {
    header('Content-Type: application/json');
    http_response_code(503);
    echo json_encode(['error' => 'OltManager not configured', 'code' => 503]);
    die;
}

$requestedEndpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
$requestedEndpoint = ltrim($requestedEndpoint, '/');

if (empty($requestedEndpoint)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Missing endpoint parameter', 'code' => 400]);
    die;
}

$method = strtoupper(isset($_GET['method']) ? $_GET['method'] : 'GET');

// Flaga trybu redirect (HTTP 302 do OltManagera).
$isRedirectMode = isset($_GET['redirect']) && ($_GET['redirect'] === '1' || $_GET['redirect'] === true);

// Zbuduj pelny URL
$targetUrl = $oltManagerUrl . '/' . $requestedEndpoint;

// W trybie redirect nie proxy'ujemy HTML - robimy realny redirect,
// dzieki czemu CSS/JS laduja se bezposrednio z OltManagera.
if ($isRedirectMode) {
    if ($isAutomaticLoginEnabled) {
        $separator = (strpos($targetUrl, '?') === false) ? '?' : '&';
        $targetUrl .= $separator . 'x-auth-token=' . rawurlencode($token);
        $targetUrl .= '&x-auth-additional-token=' . rawurlencode($omi->getMyToken());
    }

    header('Location: ' . $targetUrl, true, 302);
    die;
}

// Dla trybu API potrzebujemy host/port m.in. do CURLOPT_RESOLVE.
$parsedUrl = parse_url($oltManagerUrl);
$urlHost   = $parsedUrl['host'] ?? '';
$urlPort   = $parsedUrl['port'] ?? (($parsedUrl['scheme'] ?? 'https') === 'https' ? 443 : 80);

$headers = [
    'X-AUTH-TOKEN: ' . $token,
    'Accept: application/json',
    'Content-Type: application/json',
];

/**
 * Wykonaj żądanie cURL z opcjonalnym wymuszeniem IP (CURLOPT_RESOLVE).
 * CURLOPT_RESOLVE pozwala połączyć się po konkretnym IP zachowując SNI i nagłówek Host
 * – dzięki temu certyfikat SSL wystawiony na domenę przejdzie weryfikację.
 */
function omiProxyCurlRequest(
    string $url,
    string $method,
    array  $headers,
    bool   $sslVerify,
    string $urlHost,
    int    $urlPort,
    string $directIp = ''
): array {
    $sslVersions = [
        CURL_SSLVERSION_DEFAULT,
        CURL_SSLVERSION_TLSv1_2,
    ];

    // TLS 1.3 moze nie byc dostepny na starszych wersjach PHP/cURL.
    if (defined('CURL_SSLVERSION_TLSv1_3')) {
        $sslVersions[] = CURL_SSLVERSION_TLSv1_3;
    }

    $lastError = null;
    $lastErrno = null;
    $lastInfo  = [];

    foreach ($sslVersions as $sslVersion) {
        $curl = curl_init();

        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FAILONERROR    => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
            CURLOPT_SSLVERSION     => $sslVersion,
            CURLOPT_USERAGENT      => 'LMSOmiPlugin/1.0',
            CURLOPT_ENCODING       => '',
        ];

        // Jeśli podano bezpośredni IP – wymuś połączenie po IP zachowując SNI/Host
        // Format CURLOPT_RESOLVE: "hostname:port:ip"
        if (!empty($directIp) && !empty($urlHost)) {
            $opts[CURLOPT_RESOLVE] = [
                $urlHost . ':' . $urlPort . ':' . $directIp,
            ];
        }

        if ($method === 'POST') {
            $opts[CURLOPT_POST]       = true;
            $opts[CURLOPT_POSTFIELDS] = '{}';
        }

        curl_setopt_array($curl, $opts);

        $response = curl_exec($curl);
        $errno    = curl_errno($curl);
        $error    = curl_error($curl);
        $info     = curl_getinfo($curl);
        curl_close($curl);

        if ($errno === 0 && $response !== false) {
            return [
                'success'   => true,
                'response'  => $response,
                'http_code' => (int)$info['http_code'],
            ];
        }

        $lastError = $error;
        $lastErrno = $errno;
        $lastInfo  = $info;

        error_log('[omiapiproxy] SSL version ' . $sslVersion . ' failed. errno=' . $errno . ' error=' . $error);
    }

    error_log('[omiapiproxy] All attempts failed. Last error: ' . $lastError);
    error_log('[omiapiproxy] Target URL: ' . $url . ' | directIp: ' . ($directIp ?: 'none'));
    error_log('[omiapiproxy] ssl_verify_result: ' . ($lastInfo['ssl_verify_result'] ?? 'n/a'));

    return [
        'success' => false,
        'error'   => $lastError,
        'errno'   => $lastErrno,
        'info'    => $lastInfo,
    ];
}

$result = omiProxyCurlRequest(
    $targetUrl,
    $method,
    $headers,
    $sslVerify,
    $urlHost,
    (int)$urlPort,
    $oltManagerDirectIp
);

if (!$result['success']) {
    header('Content-Type: application/json');
    http_response_code(502);
    echo json_encode([
        'error'             => 'Upstream connection error',
        'detail'            => $result['error'],
        'errno'             => $result['errno'],
        'ssl_verify'        => $sslVerify,
        'ssl_verify_result' => $result['info']['ssl_verify_result'] ?? null,
        'target_host'       => $urlHost,
        'direct_ip'         => $oltManagerDirectIp ?: null,
        'code'              => 502,
    ]);
    die;
}

http_response_code($result['http_code']);

// API mode - zwroc JSON
header('Content-Type: application/json');
echo $result['response'];
die;

