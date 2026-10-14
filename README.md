# LMS OltManager integration plugin (LMSOmiPlugin)

Wtyczka do systemu LMS realizująca integrację z systemem OltManager.

- udostępnia OltManagerowi dane z LMS (klienci, komputery, urządzenia sieciowe, węzły, taryfy,
  konfiguracja dostępu, grupy komputerów, ONU z pluginu GPON ZTE, dane PPPoE) przez
  **API v3** (`?m=omiapi`),
- dodaje do kart klienta, komputera, urządzenia sieciowego i zgłoszenia panel z listą ONU
  (adres, MAC, serial, model, status, sygnał) pobieraną z OltManagera przez serwer LMS,
- pozwala przejść jednym kliknięciem do OltManagera, z automatycznym logowaniem (SSO).

Dane są synchronizowane po stronie OltManagera co określony czas, więc po pierwszym
uruchomieniu należy poczekać na pierwszą synchronizację - do tego czasu lista ONU będzie pusta.

## Wymagania

- LMS (lmsgit lub LMS+) na **MySQL/MariaDB albo PostgreSQL**,
- PHP **7.4 - 8.5** z rozszerzeniem `curl`,
- OltManager z włączoną integracją LMS (LMSv2) **w wersji obsługującej API v3 i SSO**.

> **Uwaga:** plugin 3.x działa wyłącznie z OltManagerem obsługującym API v3 / SSO - nie zawiera
> już starych modułów `omidatagetter` / `omiapidatagetter`. Jeśli Twój OltManager nie obsługuje
> jeszcze API v3, zostań przy pluginie **2.x** do czasu aktualizacji OltManagera.

## Instalacja

1. Pobieramy najnowszą wersję wtyczki: https://github.com/vebcc/lms-omi-plugin/releases
2. Rozpakowany katalog `LMSOmiPlugin` kopiujemy do katalogu `plugins` LMS-a (np.
   `/var/www/html/lms/plugins/LMSOmiPlugin`). Jeśli zgodnie z `lms.ini` katalog pluginów znajduje się
   gdzie indziej (np. `/usr/share/lms/plugins`), kopiujemy wtyczkę tam.
3. Tworzymy dowiązanie do ikon:
   `ln -s /var/www/html/lms/plugins/LMSOmiPlugin/img /var/www/html/lms/img/LMSOmiPlugin`
4. W katalogu głównym LMS-a przebudowujemy autoloader:

   ```
   composer dump-autoload -o
   ```

   (albo `./composer update --no-dev -n`, jak przy innych pluginach). Plugin ma też własny
   autoloader, więc nowe klasy działają nawet przy nieaktualnej classmapie.
5. W LMS: `Konfiguracja > Wtyczki` - włączamy wtyczkę.
6. Przy pierwszym uruchomieniu plugin sam dopisuje brakujące ustawienia sekcji `omi`
   (`Konfiguracja > Interfejs użytkownika`, sekcja `omi`) z wartościami domyślnymi - istniejących
   ustawień nigdy nie zmienia. Uzupełniamy co najmniej `omi.olt_manager_url` i `omi.olt_manager_token`.
7. Tworzymy w LMS konto techniczne dla OltManagera z uprawnieniem `omi_api_user` i wpisujemy jego
   login/hasło w konfiguracji integracji w OltManagerze.

Jeśli wszystko przebiegło pomyślnie, w menu pojawi się zakładka **OltManager**.

### Aktualizacja z wersji 2.x

- Najpierw zaktualizuj OltManager do wersji z API v3 / SSO - plugin 3.x nie obsługuje starszych
  OltManagerów (stare moduły `omidatagetter` / `omiapidatagetter` zostały usunięte).
- Podmień katalog pluginu (i przebuduj autoloader LMS-a).
- Ustawienia zostaną dopisane automatycznie (tylko brakujące).
- Zmiany zachowania:
  - lista **Urządzenia z błędami** jest domyślnie **wyłączona** (`omi.device_error_list_enabled`),
  - wartości logiczne są czytane jak w LMS (`true/false`, `tak/nie`, `1/0`...) - dawniej np.
    `omi.olt_manager_force_sync = false` było traktowane jako włączone,
  - proxy `?m=omiapiproxy` przyjmuje tylko zapytania GET i ścieżki względne; akcje na ONU (np. restart)
    idą przez nowy moduł `?m=omiapiaction` (POST + token CSRF) i wymagają uprawnienia `omi_full_access`,
  - uprawnienia zostały poprawione (patrz niżej) - warto sprawdzić przypisania użytkowników,
  - automatyczne logowanie działa wyłącznie przez SSO - tokeny nie trafiają już do adresu URL,
  - uprawnienie `omi_api_user` obejmuje tylko moduł `omiapi`,
  - zapytanie o konfigurację dostępu (taryfy komputerów) działa również na PostgreSQL.
- Po przejściu OltManagera na API v3 wykona on jednorazowo pełną synchronizację (inne sumy
  kontrolne kolekcji) - to normalne.

## Moduły

| moduł | opis | uprawnienie |
|---|---|---|
| `omiapi` | API v3 dla OltManagera ([docs/API_V3.md](docs/API_V3.md)) | `omi_api_user` |
| `omiredirect` | przejście do OltManagera (z SSO) - menu *OltManager* | `omi_read_only` |
| `omiapiproxy` | odczyt z API OltManagera dla paneli ONU (tylko GET) | `omi_read_only` |
| `omiapiaction` | akcje na ONU (POST + CSRF) | `omi_full_access` |
| `omideviceerrorlist` | lista urządzeń z błędnym adresem ONU w opisie (domyślnie wyłączona) | `omi_device_error_list` |

**Urządzenia z błędami** - lista komputerów i urządzeń sieciowych, które mają niepoprawnie zapisany
adres ONU w opisie (np. `TU_1/2/3:4`) i przez to nie zostaną zsynchronizowane z OltManagerem. Moduł
jest przydatny wyłącznie, jeśli adresy ONU są przechowywane w opisach - dla większości instalacji
jest zbędny, dlatego domyślnie jest wyłączony (brak pozycji w menu, moduł zachowuje się jak
nieistniejący). Włączamy go ustawieniem `omi.device_error_list_enabled = true`.

## Ustawienia (sekcja `omi`)

| ustawienie | domyślnie | opis |
|---|---|---|
| `olt_manager_url` | *(puste)* | Pełny adres OltManagera, np. `https://oltmanager.example.com` (bez końcowego `/`). |
| `olt_manager_token` | *(puste)* | Token API użytkownika integracyjnego OltManagera (rola `ROLE_INTEGRATION_USER`). Konto w OltManagerze powinno mieć wyłącznie uprawnienia integracji. Token nigdy nie trafia do przeglądarki. |
| `olt_manager_ip` | *(puste)* | Opcjonalny bezpośredni adres IP OltManagera (np. `192.168.5.55`), gdy DNS wskazuje adres niedostępny z serwera LMS. Połączenie idzie po IP z zachowaniem nazwy hosta (SNI, certyfikat). |
| `ssl_verify` | `true` | Weryfikacja certyfikatu TLS OltManagera. |
| `olt_manager_automatic_login` | `false` | Automatyczne logowanie do OltManagera (SSO) przy przejściu z LMS. W OltManagerze musi być włączone ustawienie `allow_automatic_login_via_integration_user`. |
| `olt_manager_open_in_new_tab` | `false` | Otwieranie OltManagera w nowej karcie. |
| `olt_manager_force_sync` | `false` | Po zapisaniu klienta / komputera / zobowiązania LMS prosi OltManager o natychmiastową synchronizację. |
| `olt_manager_onu_link_params` | `?enabled=1` | Dodatkowe parametry filtrowania listy ONU w panelach. |
| `olt_manager_return_customer_email` | `false` | Zwracanie e-maila klienta (pierwszy aktywny kontakt e-mail) w polu `email` kolekcji klientów. Dane osobowe (RODO) - domyślnie wyłączone. |
| `integration_slug` | *(puste)* | Slug instancji integracji OltManagera przypisanej do tego LMS-a (`Konfiguracja → Integracje`, kolumna „Slug", domyślnie `lmsv2`). Potrzebne, gdy do jednego OltManagera podłączonych jest kilka LMS-ów - zapytania o listę ONU są wtedy zawężane do właściwej instancji. |
| `integration_instance_id` | *(puste)* | Numeryczne ID instancji integracji - alternatywa dla `integration_slug`, ma pierwszeństwo. |
| `node_access_configuration_at` | *(puste)* | Uwzględniaj w konfiguracji dostępu tylko zobowiązania naliczane w podanym dniu (np. `1`). Puste / `null` = wszystkie. |
| `node_access_configuration_ignore_groups` | *(puste)* | ID grup komputerów pomijanych w konfiguracji dostępu, oddzielone przecinkami (np. `1,2,3`). |
| `provider_type` | `description` | Źródło adresów ONU dla listy urządzeń z błędami (obecnie tylko `description`). |
| `device_error_list_enabled` | `false` | Włącza moduł *Urządzenia z błędami*. |
| `deviceerrorlist_pagelimit` | `100` | Liczba pozycji na stronie listy urządzeń z błędami. |

## Uprawnienia

- `omi_api_user` - konto techniczne OltManagera: wyłącznie moduł `omiapi`.
  Użytkownik z wyłącznie tym uprawnieniem nie ma dostępu do interfejsu LMS (np. danych typu PESEL).
- `omi_read_only` - użytkownicy: przejście do OltManagera (`omiredirect`) i podgląd list ONU
  (`omiapiproxy`), bez wykonywania akcji na ONU.
- `omi_full_access` - wszystkie moduły pluginu, w tym akcje na ONU (`omiapiaction`).
- `omi_device_error_list` - lista urządzeń z błędami (widoczne tylko przy
  `omi.device_error_list_enabled = true`).

## API v3

Pełny opis: [docs/API_V3.md](docs/API_V3.md). W skrócie:

- `POST <lms>/?m=omiapi&action=<akcja>` z `loginform[login]` i `loginform[pwd]` (logowanie sesyjne LMS),
- odpowiedź zawsze JSON: `{"apiVersion": 3, "data": ...}` albo
  `{"apiVersion": 3, "error": {"code": "...", "message": "..."}}` z kodem HTTP 4xx/5xx,
- akcje: `info`, `customers`, `nodes`, `netdevices`, `netnodes`, `tariffs`,
  `nodeAccessConfigurations`, `nodeGroups`, `gponZteOnus`, `pppoeCredentials`,
- kolekcje zwracają `{"checksum": "...", "notModified": false, "items": [...]}`; z parametrem
  `ifChecksum=<md5>` równym bieżącej sumie - `{"checksum": "...", "notModified": true}` bez `items`
  (pobranie warunkowe: kolekcja jest budowana raz, a niezmienione dane nie są przesyłane).

Przykłady:

```
/?m=omiapi&action=info
/?m=omiapi&action=customers&ifChecksum=0cc175b9c0f1b6a831c399e269772661
/?m=omiapi&action=pppoeCredentials&mac=78:31:FF:27:FF:9A&upMacs=2&downMacs=2
```

Ostatni przykład szuka danych PPPoE dla MAC `78:31:FF:27:FF:9A`, a jeśli nie znajdzie - kolejno dla
`…:9B`, `…:9C`, `…:99`, `…:98`.

## Automatyczne logowanie (SSO)

Przy `omi.olt_manager_automatic_login = true` kliknięcie w link do OltManagera (menu, panel ONU)
przechodzi przez LMS (`?m=omiredirect` lub proxy w trybie przekierowania):

1. serwer LMS prosi OltManager (server-to-server, z tokenem API) o jednorazowy ticket dla loginu
   bieżącego użytkownika LMS i docelowej strony,
2. przeglądarka jest przekierowana na adres logowania z ticketem - ticket jest ważny 60 sekund
   i jednorazowy, a token API nigdy nie trafia do przeglądarki,
3. użytkownik OltManagera jest dopasowywany po loginie LMS (pole *additional username*, a w drugiej
   kolejności login OltManagera).

Jeśli OltManager nie obsługuje SSO (stara wersja), użytkownik jest przekierowany bez logowania, a w
logu błędów PHP pojawia się wpis z sugestią aktualizacji OltManagera. Każdy inny błąd również kończy
się zwykłym przekierowaniem bez logowania i wpisem w logu (bez tokenów).

## Rozwój

```
composer update          # narzędzia deweloperskie (phpunit, phpstan, php-cs-fixer)
composer test            # testy jednostkowe
composer phpstan         # analiza statyczna
composer cs              # PSR-12
php bin/generate-install-sql.php   # po zmianie listy ustawień (OmiConfig::DEFINITIONS)
```

Kod pluginu musi się parsować na PHP 7.4 - 8.5 (plugin jest ładowany przy każdym żądaniu LMS).
Zapytania SQL muszą działać na MySQL i PostgreSQL. Nowe ustawienia dodajemy w
`lib/src/Config/OmiConfig.php`, a zmiany schematu przez `PLUGIN_DB_VERSION` i pliki
`lib/upgradedb/{mysql,postgres}.<RRRRMMDDGG>.php` (nowe instalacje: `doc/lms.{mysql,pgsql}`).
