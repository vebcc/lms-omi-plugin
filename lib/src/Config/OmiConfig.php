<?php

declare(strict_types=1);

namespace Omi\Config;

/**
 * Wszystkie ustawienia sekcji "omi" w jednym miejscu: nazwa, typ, wartość domyślna i opis.
 *
 * Lista DEFINITIONS jest też źródłem dla rejestracji ustawień w bazie (doc/lms.*sql,
 * lib/upgradedb) - test SettingsRegistrationTest pilnuje, żeby się nie rozjechały.
 */
final class OmiConfig
{
    public const SECTION = 'omi';

    public const TYPE_STRING = 'string';
    public const TYPE_BOOL = 'bool';
    public const TYPE_INT = 'int';

    /**
     * @var array<string, array{type: string, default: string, description: string}>
     */
    public const DEFINITIONS = [
        'olt_manager_url' => [
            'type' => self::TYPE_STRING,
            'default' => '',
            'description' => 'Pełny adres aplikacji OltManager, np. https://oltmanager.example.com (bez końcowego ukośnika)',
        ],
        'olt_manager_token' => [
            'type' => self::TYPE_STRING,
            'default' => '',
            'description' => 'Token API użytkownika integracyjnego OltManagera (ROLE_INTEGRATION_USER)',
        ],
        'olt_manager_ip' => [
            'type' => self::TYPE_STRING,
            'default' => '',
            'description' => 'Opcjonalny bezpośredni adres IP serwera OltManagera (gdy DNS wskazuje adres niedostępny z LMS)',
        ],
        'ssl_verify' => [
            'type' => self::TYPE_BOOL,
            'default' => 'true',
            'description' => 'Weryfikacja certyfikatu TLS OltManagera',
        ],
        'olt_manager_automatic_login' => [
            'type' => self::TYPE_BOOL,
            'default' => 'false',
            'description' => 'Automatyczne logowanie do OltManagera (SSO) z przycisków i menu',
        ],
        'olt_manager_open_in_new_tab' => [
            'type' => self::TYPE_BOOL,
            'default' => 'false',
            'description' => 'Otwieranie linków do OltManagera w nowej karcie',
        ],
        'olt_manager_force_sync' => [
            'type' => self::TYPE_BOOL,
            'default' => 'false',
            'description' => 'Wymuszenie synchronizacji w OltManagerze po zapisie klienta lub komputera',
        ],
        'olt_manager_onu_link_params' => [
            'type' => self::TYPE_STRING,
            'default' => '?enabled=1',
            'description' => 'Dodatkowe parametry filtrowania listy ONU (zapytanie do API OltManagera)',
        ],
        'olt_manager_return_customer_email' => [
            'type' => self::TYPE_BOOL,
            'default' => 'false',
            'description' => 'Zwracanie adresu e-mail klienta w danych dla OltManagera (dane osobowe)',
        ],
        'integration_slug' => [
            'type' => self::TYPE_STRING,
            'default' => '',
            'description' => 'Slug instancji integracji OltManagera przypisanej do tego LMS',
        ],
        'integration_instance_id' => [
            'type' => self::TYPE_STRING,
            'default' => '',
            'description' => 'Numeryczne ID instancji integracji OltManagera (ma pierwszeństwo przed integration_slug)',
        ],
        'node_access_configuration_at' => [
            'type' => self::TYPE_INT,
            'default' => '',
            'description' => 'Uwzględniaj tylko zobowiązania naliczane w podanym dniu miesiąca (puste = wszystkie)',
        ],
        'node_access_configuration_ignore_groups' => [
            'type' => self::TYPE_STRING,
            'default' => '',
            'description' => 'ID grup komputerów pomijanych w konfiguracji dostępu, oddzielone przecinkami',
        ],
        'provider_type' => [
            'type' => self::TYPE_STRING,
            'default' => 'description',
            'description' => 'Źródło adresów ONU dla listy urządzeń z błędami (obecnie tylko description)',
        ],
        'device_error_list_enabled' => [
            'type' => self::TYPE_BOOL,
            'default' => 'false',
            'description' => 'Włącza moduł Urządzenia z błędami (adresy ONU w opisach komputerów)',
        ],
        'deviceerrorlist_pagelimit' => [
            'type' => self::TYPE_INT,
            'default' => '100',
            'description' => 'Liczba pozycji na stronie listy urządzeń z błędami',
        ],
    ];

    private ConfigSource $source;

    public function __construct(ConfigSource $source)
    {
        $this->source = $source;
    }

    public function oltManagerUrl(): string
    {
        return rtrim(trim($this->string('olt_manager_url')), '/');
    }

    public function oltManagerToken(): string
    {
        return trim($this->string('olt_manager_token'));
    }

    public function oltManagerDirectIp(): string
    {
        return trim($this->string('olt_manager_ip'));
    }

    public function isOltManagerConfigured(): bool
    {
        return $this->oltManagerUrl() !== '' && $this->oltManagerToken() !== '';
    }

    public function sslVerify(): bool
    {
        return $this->bool('ssl_verify');
    }

    public function automaticLogin(): bool
    {
        return $this->bool('olt_manager_automatic_login');
    }

    public function openInNewTab(): bool
    {
        return $this->bool('olt_manager_open_in_new_tab');
    }

    public function forceSync(): bool
    {
        return $this->bool('olt_manager_force_sync');
    }

    public function onuLinkParams(): string
    {
        return $this->string('olt_manager_onu_link_params');
    }

    public function returnCustomerEmail(): bool
    {
        return $this->bool('olt_manager_return_customer_email');
    }

    public function providerType(): string
    {
        return $this->string('provider_type');
    }

    public function deviceErrorListEnabled(): bool
    {
        return $this->bool('device_error_list_enabled');
    }

    public function deviceErrorListPageLimit(): int
    {
        $value = $this->nullableInt('deviceerrorlist_pagelimit');

        return $value !== null && $value > 0 ? $value : (int) self::DEFINITIONS['deviceerrorlist_pagelimit']['default'];
    }

    /**
     * Dzień naliczania zobowiązań (assignments.at) albo null = bez filtra.
     */
    public function nodeAccessConfigurationAt(): ?int
    {
        return $this->nullableInt('node_access_configuration_at');
    }

    /**
     * @return list<int>
     */
    public function nodeAccessConfigurationIgnoreGroups(): array
    {
        $raw = $this->nullableString('node_access_configuration_ignore_groups');
        if ($raw === null) {
            return [];
        }

        $ids = [];
        foreach (preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
            if (ctype_digit($part) && (int) $part > 0) {
                $ids[(int) $part] = (int) $part;
            }
        }

        return array_values($ids);
    }

    /**
     * Parametr wskazujący instancję integracji OltManagera ("integrationInstanceId=..." albo
     * "integrationSlug=..."), pusty gdy nie skonfigurowano. Numeryczne ID ma pierwszeństwo.
     */
    public function integrationInstanceParam(): string
    {
        $instanceId = $this->nullableString('integration_instance_id');
        if ($instanceId !== null) {
            return 'integrationInstanceId=' . rawurlencode($instanceId);
        }

        $slug = $this->nullableString('integration_slug');
        if ($slug !== null) {
            return 'integrationSlug=' . rawurlencode($slug);
        }

        return '';
    }

    private function string(string $key): string
    {
        $value = $this->source->getValue(self::SECTION . '.' . $key);

        return $value ?? self::DEFINITIONS[$key]['default'];
    }

    /**
     * Wartość tekstowa albo null dla pustej / "null" (konwencja starszych wersji pluginu).
     */
    private function nullableString(string $key): ?string
    {
        $value = trim($this->string($key));
        if ($value === '' || strtolower($value) === 'null') {
            return null;
        }

        return $value;
    }

    private function nullableInt(string $key): ?int
    {
        $value = $this->nullableString($key);
        if ($value === null || !preg_match('/^-?\d+$/', $value)) {
            return null;
        }

        return (int) $value;
    }

    private function bool(string $key): bool
    {
        return $this->source->getBool(
            self::SECTION . '.' . $key,
            BoolParser::parse(self::DEFINITIONS[$key]['default'], false)
        );
    }
}
