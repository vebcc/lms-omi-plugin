<?php

declare(strict_types=1);

namespace Omi\Legacy;

/**
 * Wysyłanie odpowiedzi legacy (JSON jak w pluginie 2.x) z obsługą wyjątków
 * bez ujawniania ścieżek plików klientowi.
 */
final class LegacyResponder
{
    /**
     * @param callable(): mixed $handler
     */
    public static function run(string $module, string $type, callable $handler): void
    {
        $status = 200;
        try {
            $data = $handler();
        } catch (\Throwable $e) {
            error_log(sprintf(
                '[LMSOmiPlugin] [%s] type=%s %s: %s @ %s:%d',
                $module,
                substr($type, 0, 100),
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
            $status = 500;
            $data = self::internalError();
        }

        self::send($data, $status);
        die;
    }

    /**
     * @return array{exception: string, code: int}
     */
    public static function internalError(): array
    {
        return ['exception' => 'Internal error, see the LMS error log for details', 'code' => 500];
    }

    /**
     * @param mixed $data
     */
    public static function send($data, int $status = 200): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json');
        }
        $json = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        echo $json === false ? 'null' : $json;
    }
}
