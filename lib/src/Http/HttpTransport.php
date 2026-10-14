<?php

declare(strict_types=1);

namespace Omi\Http;

interface HttpTransport
{
    public function send(HttpRequest $request): HttpResult;
}
