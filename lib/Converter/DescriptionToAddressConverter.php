<?php


class DescriptionToAddressConverter
{
    public function convert(?string $description): ?array
    {
        //Brak notatki
        if($description == "" || !$description){
            return null;
        }

        $result = preg_split('/\r\n|\r|\n/', $description);

        $addressToParse = $result[0];

        //Znak pominięcia obiektu lub pusta linia (która świadczy o tym samym)
        if($addressToParse == "-" || $addressToParse == ""){
            return null;
        }

        $spp = explode('_', $addressToParse);

        //Brak informacji o olcie w adresie onu (np. "TU_")
        if(!key_exists(1, $spp)){
            return null;
        }

        $oltShortName = $spp[0];

        $spp2 = explode(':', $spp[1]);

        //Błędny adres onu w notatce (brak dwukropka).
        if(!key_exists(1, $spp2)){
            return null;
        }

        //Błędny adres onu w notatce (prawdopodobnie literowka i wpisany znak zamiast liczby).
        if(!is_numeric($spp2[1])){
            return null;
        }


        $onuTagId = (int)$spp2[1];

        //Brak id onu w adresie. (Niepełny adres onu w notatce)
        if($onuTagId == ""){
            return null;
        }

        $partOfAddress = $this->getSecondPartOfAddress($spp2[0]);

        if(!$partOfAddress){
            //Błedny adres onu (Np. literówka i zamiast nr olta/karty/portu to jakaś litera) lub niewspierany wariant
            return null;
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

    private function getSecondPartOfAddress(string $addressToPrepare): ?array
    {
        $spp = explode('/', $addressToPrepare);

        if(!key_exists(1, $spp)){
            if(!is_numeric($addressToPrepare)){
                //Błedny adres onu (Np. literówka i zamiast nr portu to jakaś litera)
                return null;
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
            return null;
        }

        if(!is_numeric($spp[0]) || !is_numeric($spp[1]) || !is_numeric($spp[2])){
            //Błedny adres onu (Np. literówka i zamiast nr olta/karty/portu to jakaś litera)
            return null;
        }

        return [
            'oltTagId' => (int)$spp[0],
            'cardTagId' => (int)$spp[1],
            'portTagId' => (int)$spp[2],
        ];
    }
}