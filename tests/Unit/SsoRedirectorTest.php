<?php

declare(strict_types=1);

namespace Omi\Tests\Unit;

use Omi\Config\ArrayConfigSource;
use Omi\Config\OmiConfig;
use Omi\Http\HttpResult;
use Omi\OltManager\OltManagerClient;
use Omi\Sso\CurrentUser;
use Omi\Sso\SsoRedirector;
use Omi\Support\Logger;
use Omi\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

final class SsoRedirectorTest extends TestCase
{
    private const BASE = 'https://om.example.com';

    /** @var list<string> */
    private array $logged = [];

    /**
     * @param array<string, mixed> $settings
     * @param-out FakeTransport $transport
     */
    private function redirector(HttpResult $result, array $settings, ?FakeTransport &$transport = null): SsoRedirector
    {
        $config = new OmiConfig(new ArrayConfigSource($settings + [
            'omi.olt_manager_url' => self::BASE,
            'omi.olt_manager_token' => 'api tok&en',
        ]));
        $transport = new FakeTransport($result);
        $test = $this;
        $logger = new class ($test) extends Logger {
            private SsoRedirectorTest $test;

            public function __construct(SsoRedirectorTest $test)
            {
                parent::__construct();
                $this->test = $test;
            }

            public function error(string $message): void
            {
                $this->test->log($message);
            }
        };

        return new SsoRedirector($config, new OltManagerClient(self::BASE, 'api tok&en', $transport), $logger);
    }

    public function log(string $message): void
    {
        $this->logged[] = $message;
    }

    private static function user(): CurrentUser
    {
        return new CurrentUser(5, 'jan');
    }

    public function testAutoLoginDisabledGivesPlainRedirect(): void
    {
        $sso = $this->redirector(HttpResult::response(201, '{}'), [], $transport);

        self::assertSame(self::BASE . '/onu/12', $sso->redirectUrl('onu/12', self::user()));
        self::assertCount(0, $transport->requests);
    }

    public function testTicketIssued(): void
    {
        $body = '{"ticket":"abc","expiresIn":60,"loginUrl":"/integration/sso/consume?ticket=abc"}';
        $sso = $this->redirector(HttpResult::response(201, $body), ['omi.olt_manager_automatic_login' => 'true'], $transport);

        self::assertSame(self::BASE . '/integration/sso/consume?ticket=abc', $sso->redirectUrl('onu/12', self::user()));

        $request = $transport->requests[0];
        self::assertSame('POST', $request->method());
        self::assertSame(self::BASE . '/api/v1/integration/sso/ticket', $request->url());
        self::assertContains('X-AUTH-TOKEN: api tok&en', $request->headers());
        self::assertSame(['login' => 'jan', 'target' => '/onu/12'], json_decode((string) $request->body(), true));
    }

    public function testInvalidTargetFallsBackToRoot(): void
    {
        $body = '{"loginUrl":"/integration/sso/consume?ticket=abc"}';
        $sso = $this->redirector(HttpResult::response(201, $body), ['omi.olt_manager_automatic_login' => 'true'], $transport);
        $sso->redirectUrl('//evil.example', self::user());

        self::assertSame('/', json_decode((string) $transport->requests[0]->body(), true)['target']);
    }

    public function testUnsafeLoginUrlIsIgnored(): void
    {
        $sso = $this->redirector(
            HttpResult::response(201, '{"loginUrl":"//evil.example/x"}'),
            ['omi.olt_manager_automatic_login' => 'true']
        );

        self::assertSame(self::BASE . '/onu/1', $sso->redirectUrl('onu/1', self::user()));
    }

    public function testOldOltManagerWithoutSsoGivesPlainRedirectAndUpdateHint(): void
    {
        $sso = $this->redirector(HttpResult::response(404, '<html>Not Found</html>'), ['omi.olt_manager_automatic_login' => 'true']);

        $url = $sso->redirectUrl('onu/1?a=1', self::user());

        self::assertSame(self::BASE . '/onu/1?a=1', $url);
        self::assertStringNotContainsString('x-auth', $url);
        $log = implode("\n", $this->logged);
        self::assertStringContainsString('update OltManager', $log);
        self::assertStringNotContainsString('api tok', $log);
    }

    public function testUserNotFoundIsNotTreatedAsOldOltManager(): void
    {
        $sso = $this->redirector(
            HttpResult::response(404, '{"error":{"code":"user_not_found","message":"x"}}'),
            ['omi.olt_manager_automatic_login' => 'true']
        );

        self::assertSame(self::BASE . '/onu/1', $sso->redirectUrl('onu/1', self::user()));
        self::assertStringContainsString('user_not_found', implode("\n", $this->logged));
    }

    public function testOtherErrorsArePlainRedirectAndLoggedWithoutTokens(): void
    {
        $sso = $this->redirector(
            HttpResult::response(403, '{"error":{"code":"sso_disabled"}}'),
            ['omi.olt_manager_automatic_login' => 'true']
        );

        self::assertSame(self::BASE . '/', $sso->redirectUrl('', self::user()));
        $log = implode("\n", $this->logged);
        self::assertStringContainsString('403', $log);
        self::assertStringNotContainsString('tok', $log);
    }

    public function testTransportFailure(): void
    {
        $sso = $this->redirector(HttpResult::failure('timeout'), ['omi.olt_manager_automatic_login' => 'true']);

        self::assertSame(self::BASE . '/onu/1', $sso->redirectUrl('onu/1', self::user()));
    }

    public function testNoUser(): void
    {
        $sso = $this->redirector(HttpResult::response(201, '{}'), ['omi.olt_manager_automatic_login' => 'true'], $transport);

        self::assertSame(self::BASE . '/onu/1', $sso->redirectUrl('onu/1', null));
        self::assertCount(0, $transport->requests);
    }
}
