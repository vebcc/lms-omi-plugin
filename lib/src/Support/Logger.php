<?php

declare(strict_types=1);

namespace Omi\Support;

/**
 * Prosty logger do error_log z prefiksem pluginu.
 * Nigdy nie przekazuj tu tokenów ani haseł.
 */
class Logger
{
    private string $prefix;

    public function __construct(string $prefix = '[LMSOmiPlugin]')
    {
        $this->prefix = $prefix;
    }

    public function error(string $message): void
    {
        error_log($this->prefix . ' ' . self::sanitize($message));
    }

    public function exception(string $context, \Throwable $e): void
    {
        $this->error(sprintf(
            '%s %s: %s @ %s:%d',
            $context,
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));
    }

    /**
     * Usuwa znaki sterujące i ewentualne tokeny przekazane w query stringu.
     */
    public static function sanitize(string $message): string
    {
        $message = (string) preg_replace('/(x-auth(?:-additional)?-token|ticket|token|pwd|password)=[^&\s]*/i', '$1=***', $message);

        return (string) preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/', '', $message);
    }
}
