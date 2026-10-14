<?php

declare(strict_types=1);

namespace Omi\Tests\Unit;

use Omi\Api\Action\CollectionAction;
use Omi\Collection\Checksum;
use Omi\Collection\CollectionProvider;
use Omi\Collection\CollectionSnapshot;
use Omi\Collection\HasVolatileFields;
use PHPUnit\Framework\TestCase;

final class ChecksumTest extends TestCase
{
    /**
     * @return array<mixed>
     */
    private static function lmsList(): array
    {
        return [
            0 => ['id' => '1', 'name' => 'Zażółć', 'url' => 'a/b'],
            1 => ['id' => '2', 'name' => 'B'],
            'total' => 2,
            'order' => 'name',
            'direction' => 'asc',
        ];
    }

    public function testItemChecksumIsCompatibleWithPlugin2x(): void
    {
        $item = ['id' => '1', 'name' => 'Zażółć', 'url' => 'a/b'];

        self::assertSame(md5((string) json_encode($item)), Checksum::of($item));
    }

    public function testSnapshotDropsMetaKeysAndAddsItemChecksums(): void
    {
        $snapshot = CollectionSnapshot::fromRaw(self::lmsList());
        $items = $snapshot->items();

        self::assertCount(2, $items);
        self::assertSame([0, 1], array_keys($items));
        self::assertSame(md5((string) json_encode(['id' => '2', 'name' => 'B'])), $items[1]['checksum']);
        self::assertSame(md5((string) json_encode($items)), $snapshot->checksum());
    }

    public function testConditionalFetch(): void
    {
        $provider = new class (self::lmsList()) implements CollectionProvider {
            /** @var array<mixed> */
            private array $raw;

            /**
             * @param array<mixed> $raw
             */
            public function __construct(array $raw)
            {
                $this->raw = $raw;
            }

            public function name(): string
            {
                return 'nodes';
            }

            public function fetchRaw(): array
            {
                return $this->raw;
            }
        };
        $action = new CollectionAction($provider);

        $full = $action->handle([]);
        self::assertFalse($full['notModified']);
        self::assertCount(2, $full['items']);
        self::assertSame(md5((string) json_encode($full['items'])), $full['checksum']);

        // ta sama suma -> bez items
        $same = $action->handle(['ifChecksum' => strtoupper($full['checksum'])]);
        self::assertSame(['checksum' => $full['checksum'], 'notModified' => true], $same);

        // inna / niepoprawna suma -> pełna kolekcja, ta sama suma co bez parametru
        foreach ([md5('other'), 'garbage', ''] as $ifChecksum) {
            $changed = $action->handle(['ifChecksum' => $ifChecksum]);
            self::assertSame($full, $changed);
        }
    }

    public function testEmptyCollectionIsValid(): void
    {
        $snapshot = CollectionSnapshot::fromRaw(['total' => 0, 'order' => 'name']);

        self::assertSame([], $snapshot->items());
        self::assertSame(md5('[]'), $snapshot->checksum());
    }

    public function testInvalidUtf8DoesNotBreakChecksum(): void
    {
        self::assertNotSame(md5(''), Checksum::of(['name' => "\xB1\xEA"]));
    }

    public function testVolatileFieldsDoNotAffectV3Checksum(): void
    {
        $makeProvider = static function (string $lastOnline): CollectionProvider {
            return new class ($lastOnline) implements CollectionProvider, HasVolatileFields {
                private string $lastOnline;

                public function __construct(string $lastOnline)
                {
                    $this->lastOnline = $lastOnline;
                }

                public function name(): string
                {
                    return 'netdevices';
                }

                public function fetchRaw(): array
                {
                    return [['id' => '1', 'lastonline' => '100', 'lastonlinedate' => $this->lastOnline]];
                }

                public function volatileFields(): array
                {
                    return ['lastonlinedate'];
                }
            };
        };

        $first = (new CollectionAction($makeProvider('5 min temu')))->handle([]);
        $second = (new CollectionAction($makeProvider('6 min temu')))->handle([]);

        self::assertSame($first['checksum'], $second['checksum']);
        self::assertArrayNotHasKey('lastonlinedate', $first['items'][0]);
        self::assertSame('100', $first['items'][0]['lastonline']);
    }
}
