<?php

declare(strict_types=1);

namespace Omi\Device;

/**
 * Parsowanie adresu ONU zapisanego w pierwszej linii opisu komputera / urządzenia,
 * np. "TU_1/2/3:4" (OLT TU, rack 1, karta 2, port 3, ONU 4) albo "DS_3:4" (wariant 1:1).
 */
final class DescriptionToAddressConverter
{
    /**
     * @return array<string, mixed>|null adres, informacja o błędzie (gdy $errorInfoInResultWhenFailed) albo null
     */
    public function convert(?string $description, bool $errorInfoInResultWhenFailed = false): ?array
    {
        if ($description === null || $description === '') {
            return null;
        }

        $lines = preg_split('/\r\n|\r|\n/', $description) ?: [''];
        $addressToParse = trim($lines[0]);

        // znak pominięcia obiektu lub pusta linia
        if ($addressToParse === '-' || $addressToParse === '') {
            return null;
        }

        $parts = explode('_', $addressToParse);
        if (!array_key_exists(1, $parts)) {
            return $this->error('oltShortNameMissing', $addressToParse, $errorInfoInResultWhenFailed);
        }

        $oltShortName = $parts[0];
        $portAndOnu = explode(':', $parts[1]);

        if (!array_key_exists(1, $portAndOnu)) {
            return $this->error('colonMissing', $addressToParse, $errorInfoInResultWhenFailed);
        }
        if (!is_numeric($portAndOnu[1])) {
            return $this->error('probablyCharacter', $addressToParse, $errorInfoInResultWhenFailed);
        }

        $onuTagId = $portAndOnu[1];

        $partOfAddress = $this->portPart($portAndOnu[0], $errorInfoInResultWhenFailed);
        if ($partOfAddress === null || array_key_exists('error', $partOfAddress)) {
            return $partOfAddress;
        }

        return [
            'oltTagId' => (int) $partOfAddress['oltTagId'],
            'cardTagId' => (int) $partOfAddress['cardTagId'],
            'portTagId' => (int) $partOfAddress['portTagId'],
            'onuTagId' => (int) $onuTagId,
            'oltShortName' => $oltShortName,
            'stringAddress' => $oltShortName . '_' . $partOfAddress['oltTagId'] . '/' . $partOfAddress['cardTagId']
                . '/' . $partOfAddress['portTagId'] . ':' . $onuTagId,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function portPart(string $addressToPrepare, bool $errorInfoInResultWhenFailed): ?array
    {
        $parts = explode('/', $addressToPrepare);

        if (!array_key_exists(1, $parts)) {
            if (!is_numeric($addressToPrepare)) {
                return $this->error('colonMissing', $addressToPrepare, $errorInfoInResultWhenFailed);
            }

            // OLT-y bez informacji o stacku i karcie (np. DASAN) - wariant 1:1
            return ['oltTagId' => 0, 'cardTagId' => 0, 'portTagId' => (int) $addressToPrepare];
        }

        if (!array_key_exists(2, $parts)) {
            // wariant 1/1:1 - nieobsługiwany
            return $this->error('invalidVariant', $addressToPrepare, $errorInfoInResultWhenFailed);
        }

        if (!is_numeric($parts[0]) || !is_numeric($parts[1]) || !is_numeric($parts[2])) {
            return $this->error('colonMissing', $addressToPrepare, $errorInfoInResultWhenFailed);
        }

        return ['oltTagId' => (int) $parts[0], 'cardTagId' => (int) $parts[1], 'portTagId' => (int) $parts[2]];
    }

    /**
     * @return array{error: string, address: string}|null
     */
    private function error(string $errorName, string $address, bool $errorInfoInResultWhenFailed): ?array
    {
        if (!$errorInfoInResultWhenFailed) {
            return null;
        }

        switch ($errorName) {
            case 'colonMissing':
            case 'probablyCharacter':
            case 'onuIdMissing':
            case 'invalidVariant':
                return ['error' => $errorName, 'address' => $address];
            default:
                return null;
        }
    }
}
