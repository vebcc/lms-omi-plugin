<?php

declare(strict_types=1);

namespace Omi\Repository;

/**
 * Wyznaczanie adresu (locationAddress) i właściciela urządzenia sieciowego - logika z legacy API:
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
     * @return array{locationAddress: array<string, mixed>, ownerId: int|null}
     *         ownerId: właściciel urządzenia, a gdy brak - właściciel podłączonego komputera
     */
    public function resolve(array $netDev): array
    {
        $ownerId = $netDev['ownerid'] ? (int) $netDev['ownerid'] : null;
        $linkedOwners = $this->linkedOwners->ownersOf((int) $netDev['id']);
        $locationAddress = [];

        if ($netDev['street_ident'] && $netDev['city_ident']) {
            $locationAddress = [
                'cityIdent' => $netDev['city_ident'],
                'streetIdent' => $netDev['street_ident'],
                'location_house' => $netDev['location_house'],
            ];
        } else {
            $addressOwner = null;
            if ($ownerId !== null) {
                $addressOwner = $ownerId;
            } else {
                foreach ($linkedOwners as $linkedOwnerId) {
                    $ownerId = $linkedOwnerId;
                    if ($this->addresses->locationAddressId($linkedOwnerId) !== null) {
                        $addressOwner = $linkedOwnerId;
                        break;
                    }
                }
            }

            if ($addressOwner !== null) {
                $locationAddress = $this->addresses->locationAddress($addressOwner);
            }
        }

        $locationAddress['longitude'] = $netDev['longitude'];
        $locationAddress['latitude'] = $netDev['latitude'];

        return [
            'locationAddress' => $locationAddress,
            'ownerId' => $ownerId ?? ($linkedOwners[0] ?? null),
        ];
    }
}
