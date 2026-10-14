<?php

declare(strict_types=1);

namespace Omi\Tests\Unit;

use Omi\Api\ApiException;
use Omi\Collection\CollectionProvider;
use Omi\Legacy\LegacyApiDataGetter;
use PHPUnit\Framework\TestCase;

final class LegacyApiDataGetterTest extends TestCase
{
    /**
     * @param array<mixed> $raw
     */
    private static function provider(string $name, array $raw, bool $disabled = false): CollectionProvider
    {
        return new class ($name, $raw, $disabled) implements CollectionProvider {
            private string $name;
            /** @var array<mixed> */
            private array $raw;
            private bool $disabled;

            /**
             * @param array<mixed> $raw
             */
            public function __construct(string $name, array $raw, bool $disabled)
            {
                $this->name = $name;
                $this->raw = $raw;
                $this->disabled = $disabled;
            }

            public function name(): string
            {
                return $this->name;
            }

            public function fetchRaw(): array
            {
                if ($this->disabled) {
                    throw ApiException::featureDisabled('x');
                }

                return $this->raw;
            }
        };
    }

    private static function getter(): LegacyApiDataGetter
    {
        return new LegacyApiDataGetter([
            self::provider('nodes', [0 => ['id' => '1'], 'total' => 1, 'order' => 'name']),
            self::provider('customers', []),
            self::provider('nodeGroups', [4 => ['id' => '4', 'name' => 'g']]),
            self::provider('gponZteOnus', [], true),
        ]);
    }

    public function testCollectionKeepsLegacyShape(): void
    {
        $result = self::getter()->handle('getNodeCollection');

        self::assertSame([
            0 => ['id' => '1', 'checksum' => md5('{"id":"1"}')],
            'total' => 1,
            'order' => 'name',
        ], $result);
        self::assertSame(
            '{"0":{"id":"1","checksum":"' . md5('{"id":"1"}') . '"},"total":1,"order":"name"}',
            json_encode($result)
        );
    }

    public function testMethodNamesAreCaseInsensitiveLikeMethodExists(): void
    {
        self::assertSame(self::getter()->handle('getNodeCollection'), self::getter()->handle('GETNODECOLLECTION'));
    }

    public function testRawChecksumWithoutItemChecksums(): void
    {
        self::assertSame(
            md5((string) json_encode([0 => ['id' => '1'], 'total' => 1, 'order' => 'name'])),
            self::getter()->handle('getNodeChecksum')
        );
    }

    public function testDecoratedChecksum(): void
    {
        $expected = md5((string) json_encode([4 => ['id' => '4', 'name' => 'g', 'checksum' => md5('{"id":"4","name":"g"}')]]));

        self::assertSame($expected, self::getter()->handle('getNodeGroupCollectionChecksum'));
    }

    public function testEmptyCollectionGivesLegacyException(): void
    {
        self::assertSame(['exception' => 'Cant get customers', 'code' => 18], self::getter()->handle('getCustomerList'));
        self::assertSame(
            md5((string) json_encode(['exception' => 'Cant get customers', 'code' => 18])),
            self::getter()->handle('getCustomerChecksum')
        );
    }

    public function testMissingGponTables(): void
    {
        self::assertSame(['exception' => 'Cant get onus', 'code' => 17], self::getter()->handle('getGponZtePluginOnus'));
        self::assertSame(['exception' => 'Cant get onus', 'code' => 18], self::getter()->handle('getGponZtePluginOnusChecksum'));
    }

    public function testUnknownAndInternalMethodsAreRejected(): void
    {
        foreach (['getFromApi', 'argsHandler', '__construct', 'handle', 'nope'] as $type) {
            $result = self::getter()->handle($type);
            self::assertIsArray($result);
            self::assertSame(20, $result['code']);
            self::assertStringContainsString('dont exist', $result['exception']);
        }
    }

    public function testWhitelistCoversAllLegacyMethods(): void
    {
        $expected = [
            'getNodeCollection', 'getNodeChecksum', 'getNetDevCollection', 'getNetDevChecksum',
            'getNetNodeCollection', 'getNetNodeChecksum', 'getCustomerList', 'getCustomerChecksum',
            'getTariffs', 'getTariffsChecksum', 'getNodeAccessConfigurationCollection',
            'getNodeAccessConfigurationCollectionChecksum', 'getNodeGroupCollection',
            'getNodeGroupCollectionChecksum', 'getGponZtePluginOnus', 'getGponZtePluginOnusChecksum',
        ];

        self::assertSame(array_map('strtolower', $expected), LegacyApiDataGetter::supportedTypes());
    }
}
