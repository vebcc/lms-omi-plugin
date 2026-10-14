<?php

declare(strict_types=1);

namespace Omi\Tests\Support;

use Omi\Http\HttpRequest;
use Omi\Http\HttpResult;
use Omi\Http\HttpTransport;

final class FakeTransport implements HttpTransport
{
    /** @var list<HttpRequest> */
    public array $requests = [];
    private HttpResult $result;

    public function __construct(HttpResult $result)
    {
        $this->result = $result;
    }

    public function send(HttpRequest $request): HttpResult
    {
        $this->requests[] = $request;

        return $this->result;
    }
}
