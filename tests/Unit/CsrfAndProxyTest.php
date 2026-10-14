<?php

declare(strict_types=1);

namespace Omi\Tests\Unit;

use Omi\Config\ArrayConfigSource;
use Omi\Config\OmiConfig;
use Omi\Http\HttpResult;
use Omi\OltManager\OltManagerClient;
use Omi\Proxy\OltManagerProxy;
use Omi\Repository\UserRepository;
use Omi\Security\ArraySessionStore;
use Omi\Security\CsrfTokenManager;
use Omi\Sso\SsoRedirector;
use Omi\Support\Logger;
use Omi\Tests\Support\FakeDatabase;
use Omi\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

final class CsrfAndProxyTest extends TestCase
{
    public function testCsrfToken(): void
    {
        $session = new ArraySessionStore();
        $csrf = new CsrfTokenManager($session);

        self::assertFalse($csrf->isValid('anything'));
        $token = $csrf->token();
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        self::assertSame($token, $csrf->token());
        self::assertTrue($csrf->isValid($token));
        self::assertFalse($csrf->isValid(strtoupper($token)));
        self::assertFalse($csrf->isValid(null));
        self::assertFalse($csrf->isValid(['x']));
    }

    /**
     * @param array<string, string> $settings
     * @param-out FakeTransport $transport
     */
    private static function proxy(HttpResult $upstream, ?FakeTransport &$transport = null, array $settings = []): OltManagerProxy
    {
        $config = new OmiConfig(new ArrayConfigSource($settings + [
            'omi.olt_manager_url' => 'https://om.example.com',
            'omi.olt_manager_token' => 'tok',
        ]));
        $transport = new FakeTransport($upstream);
        $client = new OltManagerClient($config->oltManagerUrl(), $config->oltManagerToken(), $transport);
        $sso = new SsoRedirector($config, $client, new UserRepository(new FakeDatabase()), new Logger());

        return new OltManagerProxy($config, $client, $sso);
    }

    public function testReadForwardsGetWithIntegrationInstance(): void
    {
        $proxy = self::proxy(HttpResult::response(200, '{"data":[]}'), $transport, ['omi.integration_slug' => 'lmsv2']);
        $response = $proxy->read('GET', ['endpoint' => 'api/v1/onu/?enabled=1&ownerLmsId=3'], null);

        self::assertSame(200, $response->status());
        self::assertSame('{"data":[]}', $response->body());
        self::assertSame('GET', $transport->requests[0]->method());
        self::assertSame(
            'https://om.example.com/api/v1/onu/?enabled=1&ownerLmsId=3&integrationSlug=lmsv2',
            $transport->requests[0]->url()
        );
    }

    public function testReadRejectsNonGetAndInvalidEndpoints(): void
    {
        $proxy = self::proxy(HttpResult::response(200, '{}'), $transport);

        self::assertSame(405, $proxy->read('POST', ['endpoint' => 'onu/1'], null)->status());
        self::assertSame(405, $proxy->read('GET', ['endpoint' => 'onu/1', 'method' => 'POST'], null)->status());
        self::assertSame(400, $proxy->read('GET', ['endpoint' => '//evil.example'], null)->status());
        self::assertSame(400, $proxy->read('GET', ['endpoint' => 'https://evil.example'], null)->status());
        self::assertSame(400, $proxy->read('GET', [], null)->status());
        self::assertCount(0, $transport->requests);
    }

    public function testReadErrorsHideDetails(): void
    {
        $response = self::proxy(HttpResult::failure('cURL errno=60 SSL certificate problem'))->read('GET', ['endpoint' => 'onu/1'], null);

        self::assertSame(502, $response->status());
        self::assertStringNotContainsString('SSL', $response->body());

        $redirect = self::proxy(HttpResult::response(302, ''))->read('GET', ['endpoint' => 'onu/1'], null);
        self::assertSame(502, $redirect->status());
    }

    public function testNotConfigured(): void
    {
        $proxy = self::proxy(HttpResult::response(200, '{}'), $transport, ['omi.olt_manager_token' => '']);

        self::assertSame(503, $proxy->read('GET', ['endpoint' => 'onu/1'], null)->status());
    }

    public function testRedirectMode(): void
    {
        $response = self::proxy(HttpResult::response(200, '{}'), $transport)->read('GET', ['endpoint' => 'onu/5', 'redirect' => '1'], null);

        self::assertSame(302, $response->status());
        self::assertSame('https://om.example.com/onu/5', $response->location());
        self::assertCount(0, $transport->requests);
    }

    public function testActionRequiresPostCsrfAndWhitelistedEndpoint(): void
    {
        $proxy = self::proxy(HttpResult::response(200, '{"result":"success"}'), $transport);
        $endpoint = ['endpoint' => 'olt/manager/1/onu/2/run/rebootOnu'];

        self::assertSame(405, $proxy->action('GET', $endpoint, true)->status());
        self::assertSame(403, $proxy->action('POST', $endpoint, false)->status());
        self::assertSame(400, $proxy->action('POST', ['endpoint' => 'api/v1/user/delete'], true)->status());
        self::assertCount(0, $transport->requests);

        $response = $proxy->action('POST', $endpoint, true);
        self::assertSame(200, $response->status());
        self::assertSame('POST', $transport->requests[0]->method());
        self::assertSame('https://om.example.com/olt/manager/1/onu/2/run/rebootOnu', $transport->requests[0]->url());
    }
}
