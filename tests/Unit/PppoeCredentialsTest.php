<?php

declare(strict_types=1);

namespace Omi\Tests\Unit;

use Omi\Api\Action\PppoeCredentialsAction;
use Omi\Api\ApiException;
use Omi\Legacy\LegacyOmiDataGetter;
use Omi\Pppoe\PppoeCredentialsFinder;
use Omi\Tests\Support\FakeLms;
use PHPUnit\Framework\TestCase;

final class PppoeCredentialsTest extends TestCase
{
    private static function lms(): FakeLms
    {
        $lms = new FakeLms();
        $lms->nodes[7] = ['id' => '7', 'name' => 'pppoe-user', 'passwd' => 'secret'];
        $lms->macs['78:31:FF:27:FF:9C'] = 7;

        return $lms;
    }

    public function testCandidateOrder(): void
    {
        self::assertSame(
            ['78:31:FF:27:FF:9A', '78:31:FF:27:FF:9B', '78:31:FF:27:FF:9C', '78:31:FF:27:FF:99', '78:31:FF:27:FF:98'],
            PppoeCredentialsFinder::candidateMacs('78:31:ff:27:ff:9a', 2, 2)
        );
        self::assertCount(17, PppoeCredentialsFinder::candidateMacs('78:31:ff:27:ff:9a', 100, 100));
        self::assertSame([], PppoeCredentialsFinder::candidateMacs('not-a-mac', 1, 1));
    }

    public function testActionByMacWithNeighbours(): void
    {
        $action = new PppoeCredentialsAction(new PppoeCredentialsFinder(self::lms()));

        self::assertSame(
            ['nodeId' => 7, 'login' => 'pppoe-user', 'password' => 'secret'],
            $action->handle(['mac' => '78-31-FF-27-FF-9A', 'upMacs' => '2'])
        );
    }

    public function testActionByNodeId(): void
    {
        $action = new PppoeCredentialsAction(new PppoeCredentialsFinder(self::lms()));

        self::assertSame(7, $action->handle(['nodeId' => '7'])['nodeId']);
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: string}>
     */
    public static function errorProvider(): array
    {
        return [
            'no params' => [[], 'invalid_parameter'],
            'bad node id' => [['nodeId' => 'abc'], 'invalid_parameter'],
            'bad mac' => [['mac' => 'zz'], 'invalid_parameter'],
            'too many neighbours' => [['mac' => '78:31:FF:27:FF:9A', 'upMacs' => '9'], 'invalid_parameter'],
            'not found by id' => [['nodeId' => '8'], 'not_found'],
            'not found by mac' => [['mac' => '78:31:FF:27:FF:9A'], 'not_found'],
        ];
    }

    /**
     * @dataProvider errorProvider
     * @param array<string, string> $params
     */
    public function testActionErrors(array $params, string $code): void
    {
        $action = new PppoeCredentialsAction(new PppoeCredentialsFinder(self::lms()));

        try {
            $action->handle($params);
            self::fail('exception expected');
        } catch (ApiException $e) {
            self::assertSame($code, $e->errorCode());
        }
    }
}
