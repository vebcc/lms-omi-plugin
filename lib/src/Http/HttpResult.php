<?php

declare(strict_types=1);

namespace Omi\Http;

final class HttpResult
{
    private bool $transportOk;
    private int $status;
    private string $body;
    private string $error;
    private string $contentType;

    private function __construct(bool $transportOk, int $status, string $body, string $error, string $contentType)
    {
        $this->transportOk = $transportOk;
        $this->status = $status;
        $this->body = $body;
        $this->error = $error;
        $this->contentType = $contentType;
    }

    public static function response(int $status, string $body, string $contentType = ''): self
    {
        return new self(true, $status, $body, '', $contentType);
    }

    public static function failure(string $error): self
    {
        return new self(false, 0, '', $error, '');
    }

    /**
     * Czy udało się nawiązać połączenie i otrzymać odpowiedź HTTP (dowolny status).
     */
    public function isTransportOk(): bool
    {
        return $this->transportOk;
    }

    public function isSuccessful(): bool
    {
        return $this->transportOk && $this->status >= 200 && $this->status < 300;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function error(): string
    {
        return $this->error;
    }

    public function contentType(): string
    {
        return $this->contentType;
    }

    /**
     * @return array<mixed>|null
     */
    public function json(): ?array
    {
        $decoded = json_decode($this->body, true);

        return is_array($decoded) ? $decoded : null;
    }
}
