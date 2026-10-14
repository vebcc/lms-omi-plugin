<?php

declare(strict_types=1);

namespace Omi\Tests\Unit;

use Omi\Util\MacAddressCorrection;
use PHPUnit\Framework\TestCase;

final class MacAddressCorrectionTest extends TestCase
{
    public function testFormats(): void
    {
        self::assertSame('AA:BB:CC:DD:EE:0F', MacAddressCorrection::correct('aa-bb-cc-dd-ee-0f', 'canonical'));
        self::assertSame('AA-BB-CC-DD-EE-0F', MacAddressCorrection::correct('aabb.ccdd.ee0f', 'dash'));
        self::assertSame('AABBCCDDEE0F', MacAddressCorrection::correct('aa:bb:cc:dd:ee:0f', 'clear'));
        self::assertSame('aabb.ccdd.ee0f', MacAddressCorrection::correct('AA:BB:CC:DD:EE:0F'));
        self::assertNull(MacAddressCorrection::correct(null));
        self::assertNull(MacAddressCorrection::correct(''));
    }

    public function testValidation(): void
    {
        self::assertTrue(MacAddressCorrection::isValid('78:31:FF:27:FF:9A'));
        self::assertTrue(MacAddressCorrection::isValid('7831.ff27.ff9a'));
        self::assertFalse(MacAddressCorrection::isValid('78:31:FF:27:FF'));
        self::assertFalse(MacAddressCorrection::isValid('zz:31:FF:27:FF:9A'));
    }

    public function testModifyWithCarryInBothDirections(): void
    {
        self::assertSame('78:31:FF:27:ff:9b', MacAddressCorrection::modifyMacAddress('78:31:FF:27:FF:9A', 1));
        self::assertSame('78:31:FF:28:00:00', MacAddressCorrection::correct(MacAddressCorrection::modifyMacAddress('78:31:FF:27:FF:FF', 1), 'canonical'));
        self::assertSame('78:31:FF:27:FF:FF', MacAddressCorrection::correct(MacAddressCorrection::modifyMacAddress('78:31:FF:28:00:00', -1), 'canonical'));
        self::assertSame('78:31:FF:FF:FF:FF', MacAddressCorrection::correct(MacAddressCorrection::modifyMacAddress('78:31:FF:00:00:00', -1), 'canonical'));
    }
}
