<?php

namespace Utils;

class MacAddressCorrection
{


    public static function correct(?string $macAddress, string $returnedFormat = 'cisco'): ?string
    {
        if(!$macAddress){
            return null;
        }

        $clearMAC = '';
        foreach(str_split($macAddress)  as $letter){
            if($letter == ':' || $letter == '-' || $letter == '.' || $letter == ' '){
                continue;
            }

            $clearMAC .= $letter;
        }

        $correctMAC = '';

        switch ($returnedFormat){
            case 'clear':
                $correctMAC = $clearMAC;
                $correctMAC = strtoupper($correctMAC);
                break;
            case 'canonical':
            case 'dot':
                foreach(str_split($clearMAC) as $key => $letter){
                    $correctMAC .= $letter;
                    if($key % 2 != 0 && $key < 11){
                        $correctMAC .= ':';
                    }
                }
                $correctMAC = strtoupper($correctMAC);
                break;
            case 'non-canonical':
            case 'dash':
                foreach(str_split($clearMAC) as $key => $letter){
                    $correctMAC .= $letter;
                    if($key % 2 != 0 && $key < 11){
                        $correctMAC .= '-';
                    }
                }
                $correctMAC = strtoupper($correctMAC);
                break;
            default:
                foreach(str_split($clearMAC) as $key => $letter){
                    $correctMAC .= $letter;
                    if($key == 3 || $key == 7){
                        $correctMAC .= '.';
                    }
                }
                $correctMAC = strtolower($correctMAC);
                break;
        }

        return $correctMAC;
    }

    public static function decToHex($number): string
    {
        $hexValue = dechex($number);  // Konwertuj na szesnastkowy
        $hexValue = str_pad($hexValue, 2, '0', STR_PAD_LEFT);  // Uzupełnij zerami z przodu
        return $hexValue;
    }

    public static function modifyMacAddress($macAddress, $amount): string
    {
        $macVendorID = substr($macAddress, 0, 9);
        $macID = substr($macAddress, 9);

        $macParts = explode(':', $macID);
        $partCount = count($macParts);

        // Potraktuj koncowe oktety jako jedna liczbe, przesun o $amount i rozloz z powrotem.
        // Dzieki temu przeniesienie dziala w OBIE strony (poprzednia wersja obslugiwala tylko
        // przepelnienie > 255, wiec downMacs / ujemny $amount dawal zepsuty adres).
        $modulo = 1 << (8 * $partCount);

        $value = 0;
        foreach ($macParts as $part) {
            $value = ($value << 8) | (hexdec($part) & 0xFF);
        }

        $value = (($value + (int)$amount) % $modulo + $modulo) % $modulo;

        for ($i = $partCount - 1; $i >= 0; $i--) {
            $macParts[$i] = self::decToHex($value & 0xFF);
            $value >>= 8;
        }

        return $macVendorID . implode(':', $macParts);
    }
}