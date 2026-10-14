<?php

declare(strict_types=1);

namespace Omi\Util;

/**
 * Normalizacja i przesuwanie adresów MAC.
 */
final class MacAddressCorrection
{
    /**
     * @param string $returnedFormat clear | canonical (dot) | non-canonical (dash) | cisco (domyślny)
     */
    public static function correct(?string $macAddress, string $returnedFormat = 'cisco'): ?string
    {
        if ($macAddress === null || $macAddress === '') {
            return null;
        }

        $clearMac = str_replace([':', '-', '.', ' '], '', $macAddress);

        switch ($returnedFormat) {
            case 'clear':
                return strtoupper($clearMac);
            case 'canonical':
            case 'dot':
                return strtoupper(self::group($clearMac, ':'));
            case 'non-canonical':
            case 'dash':
                return strtoupper(self::group($clearMac, '-'));
            default:
                $result = '';
                foreach (str_split($clearMac) as $key => $letter) {
                    $result .= $letter;
                    if ($key === 3 || $key === 7) {
                        $result .= '.';
                    }
                }

                return strtolower($result);
        }
    }

    /**
     * Czy adres (dowolny format) to poprawny 48-bitowy MAC.
     */
    public static function isValid(string $macAddress): bool
    {
        return (bool) preg_match('/^[0-9A-Fa-f]{12}$/', str_replace([':', '-', '.', ' '], '', $macAddress));
    }

    public static function decToHex(int $number): string
    {
        return str_pad(dechex($number), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Przesuwa adres MAC w formacie kanonicznym (AA:BB:CC:DD:EE:FF) o $amount; część
     * producenta (pierwsze 3 oktety) zostaje bez zmian, przeniesienie działa w obie strony.
     */
    public static function modifyMacAddress(string $macAddress, int $amount): string
    {
        $macVendorId = substr($macAddress, 0, 9);
        $macParts = explode(':', substr($macAddress, 9));
        $partCount = count($macParts);
        $modulo = 1 << (8 * $partCount);

        $value = 0;
        foreach ($macParts as $part) {
            $value = ($value << 8) | (hexdec($part) & 0xFF);
        }

        $value = (($value + $amount) % $modulo + $modulo) % $modulo;

        for ($i = $partCount - 1; $i >= 0; $i--) {
            $macParts[$i] = self::decToHex($value & 0xFF);
            $value >>= 8;
        }

        return $macVendorId . implode(':', $macParts);
    }

    private static function group(string $clearMac, string $separator): string
    {
        $result = '';
        foreach (str_split($clearMac) as $key => $letter) {
            $result .= $letter;
            if ($key % 2 !== 0 && $key < 11) {
                $result .= $separator;
            }
        }

        return $result;
    }
}
