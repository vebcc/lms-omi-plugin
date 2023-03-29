<?php

class NetworkDeviceConnectionsByDescriptionProvider implements NetworkDeviceConnectionsProviderInterface
{
    private $db;
    private $lms;

    public function __construct()
    {
        $this->db = LMSDB::getInstance();
        $this->lms = LMS::getInstance();

    }

    public function getNetworkDeviceConnections(): array
    {
        $onuDeviceConnections = [];

        $onuDeviceConnections = $this->includeDevices($onuDeviceConnections);

        //TODO: netDev

        return $onuDeviceConnections;
    }

    private function includeDevices(array $onuDeviceConnections): array
    {
        $nodeCollection = $this->lms->GetNodeList();

        unset(
            $nodeCollection['total'],
            $nodeCollection['order'],
            $nodeCollection['direction'],
            $nodeCollection['total'],
            $nodeCollection['totalon'],
            $nodeCollection['totaloff'],
        );

        foreach ($nodeCollection as $node) {
            $address = $this->getAddressFromDescription($node['info']);
            if(!$address){
                continue;
            }

            if(key_exists($address['stringAddress'], $onuDeviceConnections)){
                $onuDeviceConnection = $onuDeviceConnections[$address['stringAddress']];
            }else{
                $onuDeviceConnection = $this->createNewOnuDeviceConnections($address);
            }

            $device = [
                'ownerId' => (int)$node['ownerid'],
                'netDev' => (int)$node['netdev'],
                'netNode' => (int)$node['netnodeid'],
                'address' => [
                    'longitude' => $node['longitude'],
                    'latitude' => $node['latitude'],
                    'cityIdent' => (int)$node['city_ident'],
                    'stateIdent' => (int)$node['state_ident'],
                    'streetIdent' => (int)$node['street_ident'],
                    'terc' => (int)$node['terc'],
                    'simc' => (int)$node['simc'],
                    'ulic' => (int)$node['ulic'],
                    'location_house' => $node['location_house']
                ],

            ];

            array_push($onuDeviceConnection['devices'], $device);

            $onuDeviceConnections[$address['stringAddress']] = $onuDeviceConnection;
        }

        return $onuDeviceConnections;
    }

    private function getNetworkDevice()
    {

    }

    private function createNewOnuDeviceConnections(array $address): array
    {
        return [
            'address' => $address,
            'devices' => [],
            'networkDevices' => [],
        ];
    }

    private function getAddressFromDescription(?string $description): ?array
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