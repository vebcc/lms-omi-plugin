<?php

declare(strict_types=1);

namespace Omi\Api;

use Omi\Support\Json;

/**
 * Koperta odpowiedzi API v3.
 */
final class ApiResponse
{
    public const API_VERSION = 3;

    private int $status;
    /** @var array<string, mixed> */
    private array $payload;

    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(int $status, array $payload)
    {
        $this->status = $status;
        $this->payload = $payload;
    }

    /**
     * @param mixed $data
     */
    public static function success($data): self
    {
        return new self(200, ['apiVersion' => self::API_VERSION, 'data' => $data]);
    }

    public static function error(ApiException $e): self
    {
        return new self($e->httpStatus(), [
            'apiVersion' => self::API_VERSION,
            'error' => ['code' => $e->errorCode(), 'message' => $e->getMessage()],
        ]);
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function body(): string
    {
        return Json::encode($this->payload);
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            header('X-Content-Type-Options: nosniff');
        }
        echo $this->body();
    }
}
