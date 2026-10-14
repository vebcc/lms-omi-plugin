<?php

declare(strict_types=1);

namespace Omi\Proxy;

use Omi\Support\Json;

/**
 * Odpowiedź modułów proxy do przeglądarki (JSON albo przekierowanie).
 */
final class ProxyResponse
{
    private int $status;
    private string $body;
    private ?string $location;

    private function __construct(int $status, string $body, ?string $location)
    {
        $this->status = $status;
        $this->body = $body;
        $this->location = $location;
    }

    public static function json(int $status, string $body): self
    {
        return new self($status, $body, null);
    }

    /**
     * Błąd dla przeglądarki - bez szczegółów technicznych (te trafiają do error_log).
     */
    public static function error(int $status, string $message): self
    {
        return new self($status, Json::encode(['error' => $message, 'code' => $status]), null);
    }

    public static function redirect(string $location): self
    {
        return new self(302, '', $location);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function location(): ?string
    {
        return $this->location;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            if ($this->location !== null) {
                header('Referrer-Policy: no-referrer');
                header('Cache-Control: no-store');
                header('Location: ' . $this->location, true, 302);

                return;
            }
            http_response_code($this->status);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            header('X-Content-Type-Options: nosniff');
        }
        echo $this->body;
    }
}
