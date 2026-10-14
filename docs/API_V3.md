# LMSOmiPlugin API v3 + SSO — kontrakt plugin ↔ OltManager

Ten dokument jest jedynym źródłem prawdy dla obu stron. Plugin wystawia, OltManager konsumuje
(z fallbackiem na legacy). Implementacja po stronie pluginu: LMSOmiPlugin 3.0.0 (`modules/omiapi.php`,
`lib/src/Api`).

## 1. Transport i uwierzytelnienie (bez zmian względem legacy)

- URL: `<lms_address>/?m=omiapi&action=<action>[&param=value...]`
- Metoda: `POST`, body `application/x-www-form-urlencoded` z `loginform[login]` i `loginform[pwd]`
  (standardowe logowanie sesyjne LMS — dokładnie tak jak dziś robi `LMSv2\Api\ApiCommunicator::getData`).
- Uprawnienie LMS: `omi_api_user` (obejmuje `omiapi` oraz legacy `omidatagetter`, `omiapidatagetter`).
- Parametry akcji zawsze w query stringu (url-encoded).

## 2. Koperta odpowiedzi

Zawsze `Content-Type: application/json; charset=utf-8`.

Sukces — HTTP 200:
```json
{ "apiVersion": 3, "data": <payload> }
```

Błąd — HTTP 4xx/5xx:
```json
{ "apiVersion": 3, "error": { "code": "<slug>", "message": "<czytelny opis>" } }
```
Kody błędów (`error.code`) i statusy HTTP:
| code | HTTP | kiedy |
|---|---|---|
| `unknown_action` | 404 | nieznana akcja |
| `invalid_parameter` | 400 | brak/zły parametr |
| `not_found` | 404 | brak rekordu (np. PPPoE dla podanego węzła/MAC) |
| `feature_disabled` | 409 | funkcja wyłączona w konfiguracji pluginu |
| `internal_error` | 500 | wyjątek; message ogólny, BEZ ścieżek plików/stack trace (szczegóły tylko w error_log) |

Wykrycie v3 po stronie OltManagera: odpowiedź `action=info` jest poprawnym JSON-em, ma
`apiVersion` (int) >= 3 i `data.actions` (lista). Cokolwiek innego (HTML, 404, strona logowania,
JSON bez `apiVersion`) ⇒ plugin legacy.

## 3. Akcje

### `info`
```json
{ "apiVersion": 3, "data": {
  "pluginVersion": "3.0.0",
  "apiVersion": 3,
  "actions": ["info","customers","nodes","netdevices","netnodes","tariffs",
              "nodeAccessConfigurations","nodeGroups","gponZteOnus","pppoeCredentials"],
  "features": { "sso": true, "customerEmail": false }
} }
```
`features.customerEmail` = wartość `omi.olt_manager_return_customer_email`.

### Kolekcje
Akcje: `customers`, `nodes`, `netdevices`, `netnodes`, `tariffs`, `nodeAccessConfigurations`,
`nodeGroups`, `gponZteOnus`.

- Bez parametrów — pełna kolekcja:
  ```json
  { "apiVersion": 3, "data": { "checksum": "<md5>", "items": [ {...}, {...} ] } }
  ```
- Z `checksumOnly=1` — tylko suma kontrolna (tania ścieżka, bez `items`):
  ```json
  { "apiVersion": 3, "data": { "checksum": "<md5>" } }
  ```
- `items` to **lista JSON** (array, nie obiekt). Brak meta-kluczy LMS (`total`, `order`,
  `direction`, `totalon`, `totaloff`, `state`, `below`, `over`) — plugin je usuwa.
- Każdy element ma **te same pola co w legacy** (żeby updatery OltManagera działały bez zmian),
  w tym per-element `checksum` tam, gdzie legacy go dodawał. Klucze elementów jak w legacy
  (np. `id`, `ownerid`, `locationAddress`, `email` gdy włączone).
- `checksum` kolekcji = `md5(json_encode(items))`; checksum przy `checksumOnly=1` MUSI być
  identyczny jak przy pełnym pobraniu (ta sama funkcja). Wartość może się różnić od legacy —
  OltManager po przejściu na v3 raz zrobi pełną synchronizację, to akceptowalne.
- Pusta kolekcja to poprawna odpowiedź `items: []` (NIE błąd, inaczej niż legacy).
- `gponZteOnus`: gdy w LMS nie ma tabel pluginu GPON ZTE ⇒ `feature_disabled` (409).
- `nodeAccessConfigurations`: SQL przenośny (MySQL + PostgreSQL) — to jest znany błąd legacy na
  PostgreSQL (`GROUP_CONCAT ... SEPARATOR`, `CURDATE()`).

### `pppoeCredentials`
Parametry: `nodeId=<int>` LUB `mac=<mac>` (+ opcjonalnie `upMacs=<0..8>`, `downMacs=<0..8>`).
```json
{ "apiVersion": 3, "data": { "nodeId": 123, "login": "...", "password": "..." } }
```
Brak dopasowania ⇒ `not_found` (404). Brak obu parametrów ⇒ `invalid_parameter` (400).

### Czego NIE ma w v3
`getUserTokens` / `getMyToken` / `getMyLogin` — logowanie zastępuje SSO (sekcja 4).
OltManager przy pluginie v3 nie synchronizuje tokenów użytkowników.
`getDevices`, `getNetworkDevices`, `getNetworkDeviceConnections*` — nieużywane przez OltManager
LMSv2; zostają tylko w legacy `omidatagetter`.

## 4. SSO (zastępuje `x-auth-token` + `x-auth-additional-token` w URL)

### 4.1 OltManager: wystawienie ticketu (server-to-server)
`POST <olt_manager_url>/api/v1/integration/sso/ticket`
- Nagłówek `X-AUTH-TOKEN: <token API użytkownika integracyjnego>` (ten sam co `omi.olt_manager_token`),
  użytkownik musi mieć `ROLE_INTEGRATION_USER`.
- Wymaga ustawienia OltManagera `allow_automatic_login_via_integration_user` = true,
  inaczej 403 `{"error":{"code":"sso_disabled",...}}`.
- Body JSON: `{ "login": "<login LMS>", "target": "/onu/123" }` (`target` opcjonalny).
- Mapowanie użytkownika: `User.additionalUsername == login`, fallback `User.username == login`
  (jak dziś w `UserAuthenticationDataUpdater`); użytkownik musi być aktywny.
- `target` akceptowany tylko jako ścieżka względna zaczynająca się od pojedynczego `/`
  (bez `//`, bez schematu, bez `\`); inaczej ignorowany (⇒ `/`).
- Odpowiedź 201:
  ```json
  { "ticket": "<base64url, 32 bajty losowe>", "expiresIn": 60,
    "loginUrl": "/integration/sso/consume?ticket=<ticket>" }
  ```
- Błędy: 401 (zły token), 403 (nie integracyjny / sso_disabled), 404 `user_not_found`,
  422 `invalid_request`.
- Ticket przechowywany wyłącznie jako hash SHA-256 (cache/Redis), TTL 60 s, jednorazowy.

### 4.2 OltManager: konsumpcja ticketu (przeglądarka)
`GET <olt_manager_url>/integration/sso/consume?ticket=<ticket>`
- Atomowo zużywa ticket (drugie użycie / po TTL ⇒ 401), loguje użytkownika w firewallu `main`
  (event `security.interactive_login`), redirect 302 na zapisany `target` albo `/`.
- Odpowiedź ustawia `Referrer-Policy: no-referrer` i `Cache-Control: no-store`.

### 4.3 Plugin: przekierowanie użytkownika
`?m=omiredirect&url=<ścieżka>` (i proxy w trybie `redirect=1`):
1. Auto-login wyłączony (`omi.olt_manager_automatic_login`) ⇒ zwykły 302 na `olt_manager_url/<ścieżka>`.
2. Auto-login włączony ⇒ plugin server-side woła 4.1 z loginem bieżącego użytkownika LMS i
   `target=/<ścieżka>`; sukces ⇒ 302 na `olt_manager_url + loginUrl`.
3. OltManager odpowiada 404 na endpoint ticketu (stara wersja) ⇒ fallback legacy
   (`x-auth-token` + `x-auth-additional-token` w URL) TYLKO gdy `omi.legacy_login_fallback`
   = true (domyślnie true w okresie przejściowym, do wyłączenia przez admina); w przeciwnym razie
   zwykły redirect bez auto-loginu.
4. Każdy inny błąd ⇒ zwykły redirect bez auto-loginu + wpis w error_log (bez tokenów w logu).

## 5. Kompatybilność
- Legacy moduły `omidatagetter` i `omiapidatagetter` zostają w pluginie z identycznym kształtem
  odpowiedzi (stare OltManagery dalej działają), ale wewnętrznie korzystają z nowych serwisów.
- OltManager: przy każdej synchronizacji (z cache wyniku detekcji per instancja integracji, np.
  10 min) sprawdza `action=info`; v3 ⇒ nowe akcje, inaczej legacy. Docelowo legacy da się usunąć
  z OltManagera bez zmian w pluginie.

## 6. Uwagi implementacyjne pluginu 3.0.0

Doprecyzowania zgodne z powyższym kontraktem (bez zmiany semantyki):

- `pppoeCredentials`: gdy podano oba parametry, pierwszeństwo ma `nodeId`. `upMacs`/`downMacs`
  spoza zakresu 0..8 lub nienumeryczne ⇒ `invalid_parameter`. Niepoprawny MAC ⇒ `invalid_parameter`.
- `checksumOnly` przyjmuje `1`/`true`/`yes`. Suma kolekcji obejmuje sumy per-element, więc
  `checksumOnly=1` liczy dokładnie to samo co pełne pobranie (oszczędza transfer i serializację).
- Suma per-element = `md5(json_encode(element))` przed dodaniem pola `checksum` - identycznie jak
  w legacy, więc sumy wierszy zapisane w OltManagerze nie zmieniają się po przejściu na v3.
- SSO (4.3, krok 3): odpowiedź 404 z `error.code = user_not_found` NIE jest traktowana jako stary
  OltManager (to brak użytkownika) - plugin robi zwykłe przekierowanie i loguje błąd. Fallback legacy
  dotyczy tylko 404 bez tego kodu (brak endpointu w starej wersji).
- `loginUrl` z odpowiedzi ticketu musi być ścieżką względną (pojedynczy `/`), inaczej jest ignorowany.

### Pola elementów kolekcji

| akcja | pola elementu |
|---|---|
| `customers` | wiersz `LMS::getCustomerList()` (np. `id`, `lastname`, `name`, `address`, `zip`, `city`, ... - zależnie od wersji LMS) + `email` (tylko gdy `omi.olt_manager_return_customer_email`) + `locationAddress` (`{cityIdent:int, streetIdent:int\|null, location_house}` albo `[]`) + `checksum` |
| `nodes` | wiersz `LMS::GetNodeList()` + `checksum` |
| `netdevices` | `id, name, description, producer, model, devtype, devtypename, serialnumber, ports, ownerid, longitude, latitude, invprojectid, project, status, takenports, netnodeid, netnode, borough_name, borough_type, borough_ident, district_name, district_ident, state_name, state_ident, location_state_name, location_state, location_zip, location_country, location_city_name, location_city, city_ident, location_street_name, location_street, street_ident, location_house, location_flat, location, lastonline, customlinks, terc, simc, ulic, locationAddress, checksum` (bez `lastonlinedate` — czas względny „x temu” zmieniałby sumę kontrolną przy każdej synchronizacji; legacy nadal go zwraca) |
| `netnodes` | wiersz `LMS::GetNetNodeList([], 'name,asc')` + `checksum` |
| `tariffs` | wiersz `LMS::GetTariffs()` + `checksum` |
| `nodeAccessConfigurations` | `node_id, node_name, ip_private, ip_public, node_macs, access, warning, chkmac, group_ids, halfduplex, tariff_ids, tariff_count, downceil, downceil_n, upceil, upceil_n, customer_balance, calculated_tariff_price, checksum` |
| `nodeGroups` | `id, name, description, checksum` |
| `gponZteOnus` | `id, serialnumber, nodeid, onuid, netdevid, numport, customerid, checksum` |

`locationAddress` urządzenia sieciowego: gdy urządzenie ma adres TERYT -
`{cityIdent, streetIdent, location_house}` jako tekst (jak w legacy), w przeciwnym razie adres właściciela
(albo właściciela pierwszego podłączonego komputera) jako liczby; zawsze z dopisanymi `longitude`, `latitude`.
Wartości liczbowe z bazy są zwracane jako tekst (tak jak zwraca je LMSDB w MySQL i PostgreSQL).
