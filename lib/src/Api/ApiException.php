<?php

declare(strict_types=1);

namespace Omi\Api;

/**
 * Błąd API v3 z kodem (slug) i statusem HTTP - patrz docs/API_V3.md, sekcja 2.
 */
class ApiException extends \RuntimeException
{
    public const UNKNOWN_ACTION = 'unknown_action';
    public const INVALID_PARAMETER = 'invalid_parameter';
    public const NOT_FOUND = 'not_found';
    public const FEATURE_DISABLED = 'feature_disabled';
    public const INTERNAL_ERROR = 'internal_error';

    private string $errorCode;
    private int $httpStatus;

    public function __construct(string $errorCode, int $httpStatus, string $message)
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
        $this->httpStatus = $httpStatus;
    }

    public static function unknownAction(string $action): self
    {
        return new self(self::UNKNOWN_ACTION, 404, 'Unknown action: ' . substr($action, 0, 64));
    }

    public static function invalidParameter(string $message): self
    {
        return new self(self::INVALID_PARAMETER, 400, $message);
    }

    public static function notFound(string $message): self
    {
        return new self(self::NOT_FOUND, 404, $message);
    }

    public static function featureDisabled(string $message): self
    {
        return new self(self::FEATURE_DISABLED, 409, $message);
    }

    public static function internal(): self
    {
        return new self(self::INTERNAL_ERROR, 500, 'Internal error, see the LMS error log for details');
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}
