<?php

declare(strict_types=1);

namespace Omi\Tests\Unit;

use Omi\Api\Action\CollectionAction;
use Omi\Collection\Checksum;
use Omi\Collection\CollectionProvider;
use Omi\Collection\CollectionSnapshot;
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

    public function testItemChecksumIsLegacyCompatible(): void
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

    public function testChecksumOnlyEqualsFullFetchChecksum(): void
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
        $checksumOnly = $action->handle(['checksumOnly' => '1']);

        self::assertSame(['checksum'], array_keys($checksumOnly));
        self::assertSame($full['checksum'], $checksumOnly['checksum']);
        self::assertCount(2, $full['items']);
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
}
