<?php

namespace Utils;

class MacAddressCorrection
{


    public static function correct($macAddress, $returnedFormat = 'cisco')
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

    public static function decToHex($number)
    {
        $hexValue = dechex($number);  // Konwertuj na szesnastkowy
        $hexValue = str_pad($hexValue, 2, '0', STR_PAD_LEFT);  // Uzupełnij zerami z przodu
        return $hexValue;
    }

    public static function modifyMacAddress($macAddress, $amount)
    {
        $macVendorID = substr($macAddress, 0, 9);
        $macID = substr($macAddress, 9);

        $macParts = explode(':', $macID);
        foreach ($macParts as $key => $part) {
            $macParts[$key] = (int)str_pad(hexdec($part), 2, '0', STR_PAD_LEFT);
        }

        $index = 2;
        $macParts[$index] = $macParts[$index] + $amount;
        if($macParts[$index] > 255) {
            $macParts[$index] = $macParts[$index] - 256;
            $macParts[$index-1] = $macParts[$index-1] + 1;
        }

        foreach ($macParts as $key => $part) {
            $macParts[$key] = MACAddressCorrection::decToHex($part);

        }

        return $macVendorID . implode(':', $macParts);
    }
}