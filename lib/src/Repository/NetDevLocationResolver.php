<?php

declare(strict_types=1);

namespace Omi\Repository;

/**
 * Wyznaczanie adresu (locationAddress) urządzenia sieciowego (format zgodny z pluginem 2.x):
 * adres TERYT urządzenia, a gdy go brak - adres właściciela, a gdy brak właściciela -
 * adres pierwszego właściciela podłączonego komputera, który ma adres.
 */
class NetDevLocationResolver
{
    private CustomerAddressBook $addresses;
    private NetDevLinkedOwners $linkedOwners;

    public function __construct(CustomerAddressBook $addresses, NetDevLinkedOwners $linkedOwners)
    {
        $this->addresses = $addresses;
        $this->linkedOwners = $linkedOwners;
    }

    /**
     * @param array<string, mixed> $netDev wiersz z NetDevRepository
     * @return array<string, mixed> locationAddress (z longitude/latitude)
     */
    public function resolve(array $netDev): array
    {
        if ($netDev['street_ident'] && $netDev['city_ident']) {
            $locationAddress = [
                'cityIdent' => $netDev['city_ident'],
                'streetIdent' => $netDev['street_ident'],
                'location_house' => $netDev['location_house'],
            ];
        } else {
            $addressOwner = $netDev['ownerid'] ? (int) $netDev['ownerid'] : null;
            if ($addressOwner === null) {
                foreach ($this->linkedOwners->ownersOf((int) $netDev['id']) as $linkedOwnerId) {
                    if ($this->addresses->locationAddressId($linkedOwnerId) !== null) {
                        $addressOwner = $linkedOwnerId;
                        break;
                    }
                }
            }
            $locationAddress = $addressOwner !== null ? $this->addresses->locationAddress($addressOwner) : [];
        }

        $locationAddress['longitude'] = $netDev['longitude'];
        $locationAddress['latitude'] = $netDev['latitude'];

        return $locationAddress;
    }
}
