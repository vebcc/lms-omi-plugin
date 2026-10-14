<?php

declare(strict_types=1);

namespace Omi\Tests\Unit;

use Omi\Api\Action;
use Omi\Api\Action\InfoAction;
use Omi\Api\ApiException;
use Omi\Api\ApiResponse;
use Omi\Api\Router;
use Omi\Config\ArrayConfigSource;
use Omi\Config\OmiConfig;
use Omi\Support\Logger;
use PHPUnit\Framework\TestCase;

final class ApiRouterTest extends TestCase
{
    private function router(Action ...$actions): Router
    {
        $logger = new class () extends Logger {
            /** @var list<string> */
            public array $messages = [];

            public function error(string $message): void
            {
                $this->messages[] = $message;
            }
        };

        return new Router($actions, $logger);
    }

    private function action(string $name, callable $handler): Action
    {
        return new class ($name, $handler) implements Action {
            private string $name;
            /** @var callable */
            private $handler;

            public function __construct(string $name, callable $handler)
            {
                $this->name = $name;
                $this->handler = $handler;
            }

            public function name(): string
            {
                return $this->name;
            }

            public function handle(array $params)
            {
                return ($this->handler)($params);
            }
        };
    }

    public function testSuccessEnvelope(): void
    {
        $response = $this->router($this->action('ping', static function (): array {
            return ['pong' => true];
        }))->dispatch('ping', []);

        self::assertSame(200, $response->status());
        self::assertSame(['apiVersion' => 3, 'data' => ['pong' => true]], $response->payload());
        self::assertSame('{"apiVersion":3,"data":{"pong":true}}', $response->body());
    }

    public function testUnknownAction(): void
    {
        $response = $this->router()->dispatch('getUserTokens', []);

        self::assertSame(404, $response->status());
        self::assertSame('unknown_action', $response->payload()['error']['code']);
    }

    /**
     * @return array<string, array{0: ApiException, 1: int, 2: string}>
     */
    public static function errorProvider(): array
    {
        return [
            'invalid parameter' => [ApiException::invalidParameter('x'), 400, 'invalid_parameter'],
            'not found' => [ApiException::notFound('x'), 404, 'not_found'],
            'feature disabled' => [ApiException::featureDisabled('x'), 409, 'feature_disabled'],
        ];
    }

    /**
     * @dataProvider errorProvider
     */
    public function testTypedErrors(ApiException $exception, int $status, string $code): void
    {
        $response = $this->router($this->action('a', static function () use ($exception): void {
            throw $exception;
        }))->dispatch('a', []);

        self::assertSame($status, $response->status());
        self::assertSame(['apiVersion' => 3, 'error' => ['code' => $code, 'message' => 'x']], $response->payload());
    }

    public function testInternalErrorDoesNotLeakDetails(): void
    {
        $response = $this->router($this->action('boom', static function (): void {
            throw new \RuntimeException('SQL error in /var/www/lms/lib/LMSDB.php');
        }))->dispatch('boom', []);

        self::assertSame(500, $response->status());
        self::assertSame('internal_error', $response->payload()['error']['code']);
        self::assertStringNotContainsString('/var/www', $response->body());
        self::assertStringNotContainsString('SQL', $response->body());
    }

    public function testInfo(): void
    {
        $config = new OmiConfig(new ArrayConfigSource(['omi.olt_manager_return_customer_email' => 'true']));
        $info = (new InfoAction('3.0.0', ['customers', 'nodes'], $config))->handle([]);

        self::assertSame('3.0.0', $info['pluginVersion']);
        self::assertSame(ApiResponse::API_VERSION, $info['apiVersion']);
        self::assertSame(['info', 'customers', 'nodes'], $info['actions']);
        self::assertSame(['sso' => true, 'conditionalFetch' => true, 'customerEmail' => true], $info['features']);
    }
}
