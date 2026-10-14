<?php

declare(strict_types=1);

namespace Omi\Http;

final class HttpRequest
{
    private string $method;
    private string $url;
    /** @var list<string> */
    private array $headers;
    private ?string $body;
    private int $timeout;

    /**
     * @param list<string> $headers
     */
    public function __construct(string $method, string $url, array $headers = [], ?string $body = null, int $timeout = 30)
    {
        $this->method = strtoupper($method);
        $this->url = $url;
        $this->headers = $headers;
        $this->body = $body;
        $this->timeout = $timeout;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function url(): string
    {
        return $this->url;
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): ?string
    {
        return $this->body;
    }

    public function timeout(): int
    {
        return $this->timeout;
    }
}
