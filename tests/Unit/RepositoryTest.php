<?php

declare(strict_types=1);

namespace Omi\Tests\Unit;

use Omi\Collection\CustomerCollection;
use Omi\Config\ArrayConfigSource;
use Omi\Config\OmiConfig;
use Omi\Repository\CustomerAddressBook;
use Omi\Repository\CustomerEmails;
use Omi\Repository\NetDevLinkedOwners;
use Omi\Repository\NetDevLocationResolver;
use Omi\Repository\NodeAccessConfigurationRepository;
use Omi\Tests\Support\FakeDatabase;
use Omi\Tests\Support\FakeLms;
use PHPUnit\Framework\TestCase;

final class RepositoryTest extends TestCase
{
    /**
     * @return list<array<string, mixed>>
     */
    private static function addressRows(): array
    {
        return [
            // klient 1: domyślny adres lokalizacji (3) + korespondencyjny (1)
            ['id' => '1', 'customer_id' => '1', 'type' => '1', 'address_id' => '10', 'vaddress_id' => '10', 'house' => '1', 'location' => 'Billing 1', 'city_id' => '5', 'cityident' => '0918123', 'streetident' => '12345'],
            ['id' => '2', 'customer_id' => '1', 'type' => '3', 'address_id' => '11', 'vaddress_id' => '11', 'house' => '2A', 'location' => 'Loc 2A', 'city_id' => '5', 'cityident' => '0918123', 'streetident' => null],
            // klient 2: tylko korespondencyjny, bez TERYT
            ['id' => '3', 'customer_id' => '2', 'type' => '1', 'address_id' => '12', 'vaddress_id' => '12', 'house' => '3', 'location' => 'Plain 3', 'city_id' => null, 'cityident' => null, 'streetident' => null],
        ];
    }

    public function testAddressBookMatchesLegacySemantics(): void
    {
        $book = new CustomerAddressBook(new FakeDatabase(['FROM customer_addresses ca' => self::addressRows()]), 3, 1);

        self::assertSame(11, $book->locationAddressId(1));
        self::assertSame(['cityIdent' => 918123, 'streetIdent' => null, 'location_house' => '2A'], $book->locationAddress(1));
        self::assertSame(12, $book->locationAddressId(2));
        self::assertSame([], $book->locationAddress(2), 'adres bez miejscowości TERYT daje pusty locationAddress');
        self::assertNull($book->locationAddressId(3));
        self::assertSame([], $book->locationAddress(3));
        self::assertSame('Plain 3', $book->locationText(2));
        self::assertNull($book->locationText(3));
    }

    public function testCustomersCollectionUsesBatchQueries(): void
    {
        $db = new FakeDatabase([
            'FROM customer_addresses ca' => self::addressRows(),
            'FROM customercontacts' => [
                ['customerid' => '1', 'contact' => 'first@example.com'],
                ['customerid' => '1', 'contact' => 'second@example.com'],
            ],
        ]);
        $lms = new FakeLms();
        $lms->customerList = [
            0 => ['id' => '1', 'name' => 'A'],
            1 => ['id' => '2', 'name' => 'B'],
            'total' => 2,
            'below' => '0.00',
        ];
        $config = new OmiConfig(new ArrayConfigSource(['omi.olt_manager_return_customer_email' => 'true']));

        $raw = (new CustomerCollection($lms, new CustomerAddressBook($db, 3, 1), new CustomerEmails($db, 8, 16384), $config))->fetchRaw();

        self::assertSame('first@example.com', $raw[0]['email']);
        self::assertNull($raw[1]['email']);
        self::assertSame(['email', 'locationAddress'], array_slice(array_keys($raw[0]), -2));
        self::assertSame(2, $raw['total']);
        self::assertCount(2, $db->queries, 'jedno zapytanie o adresy i jedno o e-maile niezależnie od liczby klientów');
        $emailQueries = array_values(array_filter($db->queries, static function (array $query): bool {
            return strpos($query['sql'], 'FROM customercontacts') !== false;
        }));
        self::assertSame([8 | 16384, 8], $emailQueries[0]['params']);
    }

    public function testNetDevLocationFallsBackToLinkedNodeOwner(): void
    {
        $db = new FakeDatabase([
            'FROM customer_addresses ca' => self::addressRows(),
            'FROM vnodes n' => [
                ['netdev' => '9', 'ownerid' => '3'],
                ['netdev' => '9', 'ownerid' => '1'],
                ['netdev' => '9', 'ownerid' => '2'],
            ],
        ]);
        $resolver = new NetDevLocationResolver(new CustomerAddressBook($db, 3, 1), new NetDevLinkedOwners($db));
        $netDev = ['id' => '9', 'ownerid' => null, 'street_ident' => '99999', 'city_ident' => null, 'location_house' => null, 'longitude' => '1.5', 'latitude' => '2.5'];

        $resolved = $resolver->resolve($netDev);

        self::assertSame(1, $resolved['ownerId']);
        self::assertSame(
            ['cityIdent' => 918123, 'streetIdent' => null, 'location_house' => '2A', 'longitude' => '1.5', 'latitude' => '2.5'],
            $resolved['locationAddress']
        );

        // urządzenie z własnym adresem TERYT: wartości bez rzutowania (jak w legacy)
        $own = $resolver->resolve(['id' => '8', 'ownerid' => '2', 'street_ident' => '00001', 'city_ident' => '0918123', 'location_house' => '5', 'longitude' => null, 'latitude' => null]);
        self::assertSame(['cityIdent' => '0918123', 'streetIdent' => '00001', 'location_house' => '5', 'longitude' => null, 'latitude' => null], $own['locationAddress']);
        self::assertSame(2, $own['ownerId']);

        // brak właściciela i podłączonych komputerów
        $orphan = $resolver->resolve(['id' => '7', 'ownerid' => null, 'street_ident' => '99999', 'city_ident' => null, 'location_house' => null, 'longitude' => null, 'latitude' => null]);
        self::assertNull($orphan['ownerId']);
        self::assertSame(['longitude' => null, 'latitude' => null], $orphan['locationAddress']);
    }

    public function testNodeAccessConfigurationRowShapeAndParameters(): void
    {
        $db = new FakeDatabase([
            'GROUP BY n.id' => [[
                'node_id' => '5', 'node_name' => 'N5', 'ip_private' => '10.0.0.5', 'ip_public' => '0.0.0.0',
                'access' => '1', 'warning' => '0', 'chkmac' => '1', 'halfduplex' => '0', 'tariff_count' => '2',
                'downceil' => '100', 'downceil_n' => null, 'upceil' => '50', 'upceil_n' => null,
                'customer_balance' => '-10.00', 'calculated_tariff_price' => '49.00',
            ]],
            'SELECT DISTINCT n.id AS node_id, t.id AS value' => [
                ['node_id' => '5', 'value' => '3'],
                ['node_id' => '5', 'value' => '11'],
            ],
            'FROM nodegroupassignments nga' => [['node_id' => '5', 'value' => '2']],
            'FROM macs m' => [
                ['node_id' => '5', 'value' => 'BB:00:00:00:00:01'],
                ['node_id' => '5', 'value' => 'AA:00:00:00:00:01'],
            ],
        ]);

        $rows = (new NodeAccessConfigurationRepository($db))->findAll([4, 0, 6], 10, 1700000000);

        self::assertSame([
            'node_id', 'node_name', 'ip_private', 'ip_public', 'node_macs', 'access', 'warning', 'chkmac',
            'group_ids', 'halfduplex', 'tariff_ids', 'tariff_count', 'downceil', 'downceil_n', 'upceil',
            'upceil_n', 'customer_balance', 'calculated_tariff_price',
        ], array_keys($rows[0]));
        self::assertSame('AA:00:00:00:00:01,BB:00:00:00:00:01', $rows[0]['node_macs']);
        self::assertSame('2', $rows[0]['group_ids']);
        self::assertSame('3,11', $rows[0]['tariff_ids']);

        $main = $db->queries[0];
        self::assertSame([1700000000, 1700000000, 1, 4, 6, 10], $main['params']);
        self::assertStringContainsString('nga2.nodegroupid IN (?,?)', $main['sql']);
        self::assertStringContainsString('a.at = ?', $main['sql']);
        self::assertStringNotContainsString('GROUP_CONCAT', $main['sql']);
        self::assertStringNotContainsString('CURDATE', $main['sql']);
    }

    public function testNodeWithoutMacsAndGroupsHasNulls(): void
    {
        $db = new FakeDatabase([
            'GROUP BY n.id' => [[
                'node_id' => '6', 'node_name' => 'N6', 'ip_private' => null, 'ip_public' => null, 'access' => '1',
                'warning' => '0', 'chkmac' => '1', 'halfduplex' => '0', 'tariff_count' => '1', 'downceil' => '1',
                'downceil_n' => null, 'upceil' => '1', 'upceil_n' => null, 'customer_balance' => null,
                'calculated_tariff_price' => '1.00',
            ]],
            'SELECT DISTINCT n.id AS node_id, t.id AS value' => [['node_id' => '6', 'value' => '1']],
        ]);

        $row = (new NodeAccessConfigurationRepository($db))->findAll()[0];

        self::assertNull($row['node_macs']);
        self::assertNull($row['group_ids']);
        self::assertStringNotContainsString('a.at = ?', $db->queries[0]['sql']);
    }
}
