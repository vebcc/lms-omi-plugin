<?php

declare(strict_types=1);

namespace Omi\Tests\Unit;

use Omi\Device\DescriptionToAddressConverter;
use PHPUnit\Framework\TestCase;

final class DescriptionToAddressConverterTest extends TestCase
{
    public function testParsesFullAddress(): void
    {
        $result = (new DescriptionToAddressConverter())->convert("TU_1/2/3:4\nkomentarz");

        self::assertSame([
            'oltTagId' => 1,
            'cardTagId' => 2,
            'portTagId' => 3,
            'onuTagId' => 4,
            'oltShortName' => 'TU',
            'stringAddress' => 'TU_1/2/3:4',
        ], $result);
    }

    public function testParsesOneToOneVariant(): void
    {
        $result = (new DescriptionToAddressConverter())->convert('DS_7:12');

        self::assertNotNull($result);
        self::assertSame('DS_0/0/7:12', $result['stringAddress']);
    }

    /**
     * @dataProvider invalidProvider
     */
    public function testErrors(string $description, ?string $expectedError): void
    {
        $converter = new DescriptionToAddressConverter();

        self::assertNull($converter->convert($description));
        $withError = $converter->convert($description, true);
        self::assertSame($expectedError, $withError === null ? null : $withError['error']);
    }

    /**
     * @return array<string, array{0: string, 1: string|null}>
     */
    public static function invalidProvider(): array
    {
        return [
            'skip marker' => ['-', null],
            'no olt name' => ['1/2/3:4', null],
            'no colon' => ['TU_1/2/3', 'colonMissing'],
            'letter as onu id' => ['TU_1/2/3:x', 'probablyCharacter'],
            'two-part port' => ['TU_1/2:3', 'invalidVariant'],
            'letter in port' => ['TU_1/a/3:4', 'colonMissing'],
        ];
    }

    public function testEmptyDescription(): void
    {
        self::assertNull((new DescriptionToAddressConverter())->convert(null));
        self::assertNull((new DescriptionToAddressConverter())->convert("\nTU_1/2/3:4"));
    }
}
