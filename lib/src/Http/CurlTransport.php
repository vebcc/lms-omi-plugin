<?php

declare(strict_types=1);

namespace Omi\Http;

use Omi\Support\Logger;

/**
 * Transport cURL: bez podążania za przekierowaniami (nagłówek X-AUTH-TOKEN nie może trafić
 * na inny host), tylko http/https, opcjonalne wymuszenie IP (CURLOPT_RESOLVE) i ponowienie
 * z inną wersją TLS wyłącznie przy błędach negocjacji TLS.
 */
final class CurlTransport implements HttpTransport
{
    /** Błędy cURL, przy których warto spróbować innej wersji TLS. */
    private const TLS_RETRY_ERRNOS = [35, 56, 59];

    private bool $sslVerify;
    private string $directIp;
    private Logger $logger;
    private string $userAgent;

    public function __construct(bool $sslVerify, string $directIp, Logger $logger, string $userAgent = 'LMSOmiPlugin')
    {
        $this->sslVerify = $sslVerify;
        $this->directIp = $directIp;
        $this->logger = $logger;
        $this->userAgent = $userAgent;
    }

    public function send(HttpRequest $request): HttpResult
    {
        if (!function_exists('curl_init')) {
            return HttpResult::failure('PHP curl extension is not available');
        }

        $sslVersions = [CURL_SSLVERSION_DEFAULT, CURL_SSLVERSION_TLSv1_2];
        if (defined('CURL_SSLVERSION_TLSv1_3')) {
            $sslVersions[] = constant('CURL_SSLVERSION_TLSv1_3');
        }

        $lastError = '';
        foreach ($sslVersions as $sslVersion) {
            $curl = curl_init();
            curl_setopt_array($curl, $this->options($request, (int) $sslVersion));

            $body = curl_exec($curl);
            $errno = curl_errno($curl);
            $error = curl_error($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $contentType = (string) curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
            if (PHP_VERSION_ID < 80000) {
                curl_close($curl);
            }

            if ($errno === 0 && is_string($body)) {
                return HttpResult::response($status, $body, $contentType);
            }

            $lastError = sprintf('cURL errno=%d %s', $errno, $error);
            $this->logger->error(sprintf(
                'HTTP %s %s failed (tls=%d): %s',
                $request->method(),
                self::describeUrl($request->url()),
                $sslVersion,
                $lastError
            ));

            if (!in_array($errno, self::TLS_RETRY_ERRNOS, true)) {
                break;
            }
        }

        return HttpResult::failure($lastError);
    }

    /**
     * @return array<int, mixed>
     */
    private function options(HttpRequest $request, int $sslVersion): array
    {
        $opts = [
            CURLOPT_URL => $request->url(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => min(10, $request->timeout()),
            CURLOPT_TIMEOUT => $request->timeout(),
            CURLOPT_HTTPHEADER => $request->headers(),
            CURLOPT_FAILONERROR => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => $this->sslVerify,
            CURLOPT_SSL_VERIFYHOST => $this->sslVerify ? 2 : 0,
            CURLOPT_SSLVERSION => $sslVersion,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_ENCODING => '',
        ];

        if (defined('CURLOPT_PROTOCOLS')) {
            $opts[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        if ($request->method() === 'GET') {
            $opts[CURLOPT_HTTPGET] = true;
        } elseif ($request->method() === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $request->body() ?? '';
        } else {
            $opts[CURLOPT_CUSTOMREQUEST] = $request->method();
            if ($request->body() !== null) {
                $opts[CURLOPT_POSTFIELDS] = $request->body();
            }
        }

        $resolve = $this->resolveEntry($request->url());
        if ($resolve !== null) {
            $opts[CURLOPT_RESOLVE] = [$resolve];
        }

        return $opts;
    }

    /**
     * Wpis CURLOPT_RESOLVE "host:port:ip" - połączenie po IP z zachowaniem SNI i nagłówka Host.
     */
    private function resolveEntry(string $url): ?string
    {
        if ($this->directIp === '') {
            return null;
        }
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return null;
        }
        $port = $parts['port'] ?? ((($parts['scheme'] ?? 'https') === 'https') ? 443 : 80);

        return $parts['host'] . ':' . $port . ':' . $this->directIp;
    }

    /**
     * Opis URL-a do logów: host + ścieżka, bez query stringu.
     */
    public static function describeUrl(string $url): string
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '<invalid url>';
        }

        return ($parts['scheme'] ?? '') . '://' . ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '')
            . ($parts['path'] ?? '');
    }
}
