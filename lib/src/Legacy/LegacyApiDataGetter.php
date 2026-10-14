<?php

declare(strict_types=1);

namespace Omi\Legacy;

use Omi\Api\ApiException;
use Omi\Collection\Checksum;
use Omi\Collection\CollectionProvider;

/**
 * Adapter starego modułu ?m=omiapidatagetter&type=... (kolekcje dla OltManagera < v3).
 *
 * Kształt odpowiedzi jest identyczny jak w pluginie 2.x (łącznie z kluczami meta LMS
 * i błędami w postaci ['exception' => ..., 'code' => ...] z HTTP 200), ale typy są
 * obsługiwane wyłącznie z jawnej białej listy.
 *
 * @deprecated używaj ?m=omiapi (API v3, docs/API_V3.md)
 */
final class LegacyApiDataGetter
{
    private const MODE_COLLECTION = 'collection';
    /** suma z kolekcji z sumami per-element (tak liczył legacy) */
    private const MODE_CHECKSUM = 'checksum';
    /** suma z surowych danych LMS, bez sum per-element (tak liczył legacy) */
    private const MODE_RAW_CHECKSUM = 'raw-checksum';

    /**
     * Nazwa metody (bez rozróżniania wielkości liter, jak method_exists w legacy)
     * => [kolekcja v3, tryb, komunikat błędu, kod błędu].
     */
    private const TYPES = [
        'getnodecollection' => ['nodes', self::MODE_COLLECTION, 'Cant get nodes', 18],
        'getnodechecksum' => ['nodes', self::MODE_RAW_CHECKSUM, 'Cant get nodes', 18],
        'getnetdevcollection' => ['netdevices', self::MODE_COLLECTION, 'Cant get network devices', 18],
        'getnetdevchecksum' => ['netdevices', self::MODE_CHECKSUM, 'Cant get network devices', 18],
        'getnetnodecollection' => ['netnodes', self::MODE_COLLECTION, 'Cant get network nodes', 18],
        'getnetnodechecksum' => ['netnodes', self::MODE_RAW_CHECKSUM, 'Cant get network nodes', 18],
        'getcustomerlist' => ['customers', self::MODE_COLLECTION, 'Cant get customers', 18],
        'getcustomerchecksum' => ['customers', self::MODE_CHECKSUM, 'Cant get customers', 18],
        'gettariffs' => ['tariffs', self::MODE_COLLECTION, 'Cant get tariffs', 18],
        'gettariffschecksum' => ['tariffs', self::MODE_RAW_CHECKSUM, 'Cant get tariffs', 18],
        'getnodeaccessconfigurationcollection' => ['nodeAccessConfigurations', self::MODE_COLLECTION, 'Cant get nodeAccessConfigurationList', 18],
        'getnodeaccessconfigurationcollectionchecksum' => ['nodeAccessConfigurations', self::MODE_CHECKSUM, 'Cant get nodeAccessConfigurationList', 18],
        'getnodegroupcollection' => ['nodeGroups', self::MODE_COLLECTION, 'Cant get getNodeGroupCollection', 18],
        'getnodegroupcollectionchecksum' => ['nodeGroups', self::MODE_CHECKSUM, 'Cant get getNodeGroupCollection', 18],
        'getgponztepluginonus' => ['gponZteOnus', self::MODE_COLLECTION, 'Cant get onus', 17],
        'getgponztepluginonuschecksum' => ['gponZteOnus', self::MODE_RAW_CHECKSUM, 'Cant get onus', 18],
    ];

    /** @var array<string, CollectionProvider> */
    private array $providers = [];

    /**
     * @param list<CollectionProvider> $providers
     */
    public function __construct(array $providers)
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->name()] = $provider;
        }
    }

    /**
     * @return list<string>
     */
    public static function supportedTypes(): array
    {
        return array_keys(self::TYPES);
    }

    /**
     * @return mixed odpowiedź w formacie legacy (tablica albo string z sumą kontrolną)
     */
    public function handle(string $type)
    {
        $definition = self::TYPES[strtolower($type)] ?? null;
        if ($definition === null || !isset($this->providers[$definition[0]])) {
            return ['exception' => 'method with name: ' . substr($type, 0, 100) . ' dont exist!', 'code' => 20];
        }

        [$collection, $mode, $errorMessage, $errorCode] = $definition;
        $error = ['exception' => $errorMessage, 'code' => $errorCode];

        try {
            $raw = $this->providers[$collection]->fetchRaw();
        } catch (ApiException $e) {
            // np. brak tabel pluginu GPON ZTE - legacy zwracał wtedy pustą kolekcję
            $raw = [];
        }

        switch ($mode) {
            case self::MODE_RAW_CHECKSUM:
                return $raw === [] ? $error : Checksum::of($raw);
            case self::MODE_CHECKSUM:
                return Checksum::of($raw === [] ? $error : Checksum::withItemChecksums($raw));
            default:
                return $raw === [] ? $error : Checksum::withItemChecksums($raw);
        }
    }
}
