<?php

declare(strict_types=1);

namespace Omi\Tests\Unit;

use Omi\Proxy\IntegrationInstance;
use Omi\Proxy\OnuActionEndpoint;
use Omi\Proxy\RelativePath;
use PHPUnit\Framework\TestCase;

final class RelativePathTest extends TestCase
{
    /**
     * @dataProvider validProvider
     */
    public function testValid(string $raw, string $expected): void
    {
        self::assertSame($expected, RelativePath::normalize($raw));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function validProvider(): array
    {
        return [
            'api with query' => ['api/v1/onu/?enabled=1&ownerLmsId=5', 'api/v1/onu/?enabled=1&ownerLmsId=5'],
            'leading slash' => ['/onu/123', 'onu/123'],
            'colon in query' => ['onu/1?time=12:00', 'onu/1?time=12:00'],
            'dots in name' => ['files/a.b..c', 'files/a.b..c'],
        ];
    }

    /**
     * @dataProvider invalidProvider
     */
    public function testInvalid(string $raw): void
    {
        self::assertNull(RelativePath::normalize($raw));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidProvider(): array
    {
        return [
            'empty' => [''],
            'only slash' => ['/'],
            'scheme' => ['https://evil.example/x'],
            'javascript' => ['javascript:alert(1)'],
            'protocol relative' => ['//evil.example/x'],
            'triple slash' => ['///evil.example'],
            'backslash' => ['\\evil.example'],
            'backslash inside' => ['onu/\\x'],
            'dot dot' => ['api/../admin'],
            'trailing dot dot' => ['api/..'],
            'single dot' => ['./onu'],
            'encoded dot dot' => ['api/%2e%2e/admin'],
            'double encoded' => ['api/%252e%252e/admin'],
            'encoded backslash' => ['api/%5cadmin'],
            'encoded leading slash' => ['%2f%2fevil.example'],
            'control char' => ["onu/1\r\nX-Header: 1"],
            'null byte' => ["onu/1\0"],
            'fragment' => ['onu/1#x'],
            'too long' => [str_repeat('a', RelativePath::MAX_LENGTH + 1)],
        ];
    }

    public function testTarget(): void
    {
        self::assertSame('/onu/5', RelativePath::target('onu/5'));
        self::assertSame('/', RelativePath::target(''));
        self::assertSame('/', RelativePath::target('//evil.example'));
        self::assertSame('/tools/onu/unauthenticated/finder', RelativePath::target('tools/onu/unauthenticated/finder'));
    }

    public function testOnuActionEndpointWhitelist(): void
    {
        self::assertSame('olt/manager/3/onu/15/run/rebootOnu', OnuActionEndpoint::normalize('/olt/manager/3/onu/15/run/rebootOnu'));
        self::assertSame('olt/manager/3/onu/15/run/rebootOnu/json/base', OnuActionEndpoint::normalize('olt/manager/3/onu/15/run/rebootOnu/json/base'));
        self::assertNull(OnuActionEndpoint::normalize('api/v1/onu/'));
        self::assertNull(OnuActionEndpoint::normalize('olt/manager/3/onu/15/run/rebootOnu?x=1'));
        self::assertNull(OnuActionEndpoint::normalize('olt/manager/3/onu/15/run/../../../user/delete'));
        self::assertNull(OnuActionEndpoint::normalize('//evil/olt/manager/3/onu/15/run/rebootOnu'));
    }

    public function testIntegrationInstanceIsAppendedOnlyToHonoringEndpoints(): void
    {
        self::assertSame(
            'api/v1/onu/?enabled=1&ownerLmsId=1&integrationSlug=lmsv2',
            IntegrationInstance::apply('api/v1/onu/?enabled=1&ownerLmsId=1', 'integrationSlug=lmsv2')
        );
        self::assertSame('onu/lms/get/onus?integrationInstanceId=4', IntegrationInstance::apply('onu/lms/get/onus', 'integrationInstanceId=4'));
        self::assertSame('onu/5', IntegrationInstance::apply('onu/5', 'integrationSlug=lmsv2'));
        self::assertSame(
            'api/v1/onu/?integrationSlug=other',
            IntegrationInstance::apply('api/v1/onu/?integrationSlug=other', 'integrationSlug=lmsv2')
        );
        self::assertSame('api/v1/onu/?a=1', IntegrationInstance::apply('api/v1/onu/?a=1', ''));
    }
}
