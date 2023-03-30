<?php


class DescriptionToAddressConverter
{
    public function convert(?string $description, bool $errorInfoInResultWhenFailed = false): ?array
    {
        //Brak notatki
        if($description == "" || !$description){
            return null;
        }

        $result = preg_split('/\r\n|\r|\n/', $description);

        $addressToParse = trim($result[0]);

        //Znak pominięcia obiektu lub pusta linia (która świadczy o tym samym)
        if($addressToParse == "-" || $addressToParse == ""){
            return null;
        }

        $spp = explode('_', $addressToParse);

        //Brak informacji o olcie w adresie onu (np. "TU_")
        if(!key_exists(1, $spp)){
            return $this->errorResultProvider('oltShortNameMissing', $addressToParse, $errorInfoInResultWhenFailed);
        }

        $oltShortName = $spp[0];

        $spp2 = explode(':', $spp[1]);

        //Błędny adres onu w notatce (brak dwukropka).
        if(!key_exists(1, $spp2)){
            return $this->errorResultProvider('colonMissing', $addressToParse, $errorInfoInResultWhenFailed);
        }

        //Błędny adres onu w notatce (prawdopodobnie literowka i wpisany znak zamiast liczby).
        if(!is_numeric($spp2[1])){
            return $this->errorResultProvider('probablyCharacter', $addressToParse, $errorInfoInResultWhenFailed);
        }


        $onuTagId = $spp2[1];

        //Brak id onu w adresie. (Niepełny adres onu w notatce)
        if($onuTagId == ""){
            return $this->errorResultProvider('onuIdMissing', $addressToParse, $errorInfoInResultWhenFailed);
        }

        $partOfAddress = $this->getSecondPartOfAddress($spp2[0], $errorInfoInResultWhenFailed);

        if(!$partOfAddress || key_exists('error', $partOfAddress)){
            //Błedny adres onu (Np. literówka i zamiast nr olta/karty/portu to jakaś litera) lub niewspierany wariant
            return $partOfAddress;
        }

        return [
            'oltTagId' => $partOfAddress['oltTagId'],
            'cardTagId' => $partOfAddress['cardTagId'],
            'portTagId' => $partOfAddress['portTagId'],
            'onuTagId' => $onuTagId,
            'oltShortName' => $oltShortName,
            'stringAddress' =>
                $oltShortName .
                '_' .
                $partOfAddress['oltTagId'] .
                '/' .
                $partOfAddress['cardTagId'] .
                '/' .
                $partOfAddress['portTagId'] .
                ':' .
                $onuTagId,
        ];
    }

    private function getSecondPartOfAddress(string $addressToPrepare, bool $errorInfoInResultWhenFailed = false): ?array
    {
        $spp = explode('/', $addressToPrepare);

        if(!key_exists(1, $spp)){
            if(!is_numeric($addressToPrepare)){
                //Błedny adres onu (Np. literówka i zamiast nr portu to jakaś litera)
                return $this->errorResultProvider('colonMissing', $addressToPrepare, $errorInfoInResultWhenFailed);
            }

            //Dla takich oltow jak DASANY które nie maja ani info o stacku ani portów
            // Wariant 1:1
            return [
                'oltTagId' => 0,
                'cardTagId' => 0,
                'portTagId' => (int)$addressToPrepare,
            ];
        }

        if(!key_exists(2, $spp)){
            //Wariant w stylu 1/1:1. Obecnie nie wspierany
            return $this->errorResultProvider('invalidVariant', $addressToPrepare, $errorInfoInResultWhenFailed);
        }

        if(!is_numeric($spp[0]) || !is_numeric($spp[1]) || !is_numeric($spp[2])){
            //Błedny adres onu (Np. literówka i zamiast nr olta/karty/portu to jakaś litera)
            return $this->errorResultProvider('colonMissing', $addressToPrepare, $errorInfoInResultWhenFailed);
        }

        return [
            'oltTagId' => (int)$spp[0],
            'cardTagId' => (int)$spp[1],
            'portTagId' => (int)$spp[2],
        ];
    }

    private function errorResultProvider(string $errorName, string $address, bool $errorInfoInResultWhenFailed = false): ?array
    {
        if(!$errorInfoInResultWhenFailed){
            return null;
        }

        switch ($errorName){
            case 'colonMissing':
            case 'probablyCharacter':
            case 'onuIdMissing':
            case 'invalidVariant':
                return ['error' => $errorName, 'address' => $address];
            case 'oltShortNameMissing':
            default:
                return null;
        }
    }
}