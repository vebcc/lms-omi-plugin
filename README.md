# LMS OltManager integration plugin

Wtyczka do systemu LMS realizująca integrację do systemu OltManager.

Ze względu na różne podejście ISP do zapisywania adresów w lmsie,
konieczne było stworzenie wtyczki, która pozwoli wyciągać niezbędne dane z systemu.
"API" lms'a dodatkowo nie pozwala na pobranie wszystkich danych. Na przykład w przypadku
NetDev nie ma możliwości pobrania adresu jako TERYT.

Wtyczka dodatkowo zapewnia możliwość szybkiego przejścia do systemu OltManager
bezpośrednio z Node i NetDev przez dodanie przycisku, który kieruje bezpośrednio
na zsynchronizowane Onu.

W sekcji Customer został dodany dodatkowy panel, który wyświetla podstawowe informacje
o wszystkich urządzeniach klienta, które są zsynchronizowane z systemem OltManager.
Podejrzeć możemy informacje takie jak: adres, mac, serial, status, sygnał, model, czy czas pracy.

Należy pamiętać o poprawnej konfiguracji po stronie systemu OltManager. Bez synchronizacji
po stronie OM, żadne przyciski w Node, i NetDev nie będą wyświetlane. Synchronizacja
wykonuje się po stronie OM co określony czas, wiec po uruchomieniu należy odczekać do
wykonania 1 synchronizacji.

## Instalacja

Należy pamiętać, że wtyczka była pisana na wersji PHP 7.3 dlatego, jeżeli instancja
LMS'a jest na niższej wersji, instalacja wtyczki nie będzie możliwa.

- Pobieramy najnowszą wersję wtyczki:
  https://github.com/vebcc/lms-omi-plugin/releases
- Rozpakowany folder LMSOmiPlugin kopiujemy do folderu "plugins" który znajduje się
  w głównym katalogu LMS. W przypadku, gdy zgodnie z konfiguracją `lms.ini`, folder plugins znajduje
- się np. w `/usr/share/lms/plugins` należy skopiować wtyczkę do tego folderu. W specyficznych
  przypadkach należy wrzucić wtyczkę do obydwóch lokalizacji.
- Tworzymy dowiązanie symboliczne w katalogu img LMS-a o nazwie LMSOmiPlugin do katalogu ../plugins/LMSOmiPlugin/img.
  np. `ln -s /var/www/html/plugins/LMSOmiPlugin/img /var/www/html/img/LMSOmiPlugin`
- Przechodzimy do głównego katalogu, w którym jest zainstalowany LMS np. `/var/www/html/lms`
  i wykonujemy polecenie:

> ./composer update --no-dev -n

- Następnie przechodzimy do LMS'a do zakładki `Konfiguracja>Wtyczki` i włączamy wtyczkę.
- W zakładce `Konfiguracja>Nowe ustawienie` dodajemy ustawienia zgodnie z rozdziałem "Ustawienia" poniżej.
- Jeśli wszystko przebiegło pomyślnie, w menu pojawi się zakładka OltManager.

## Moduły

- Urządzenia z błędami - wyświetla listę z listą urządzeń, które mają
  niepoprawnie skonfigurowane adresy Onu, przez co nie zostaną zsynchronizowane
  z systemem OltManager. (Moduł użyteczny wyłącznie, jeśli przechowywujemy adresy Onu w opisach komputerów, urządzeń
  sieciowych.)

## Ustawienia

Do poprawnej pracy wtyczki niezbędne jest dodanie ustawień do systemu

Pełny adres do aplikacji OltManager
> omi.olt_manager_url

Token do autoryzacji z systemu OltManager
W systemie OltManager musi być dodane konto, które ze względów bezpieczeństwa
powinno mieć uprawnienia wyłącznie do integracji LMS. Dodatkowo należy wygenerować
token i wprowadzić go do LMS'a.
> omi.olt_manager_token

(Opcjonalny) Typ dostawcy adresów onu.
Domyślnie i obecnie jedyny wspierany: "description"
> omi.provider_type

(Opcjonalny) Automatyczne logowanie do systemu OltManager. 
Domyślnie "false", zmiana na true powoduje automatyczne dodawanie tokenów autoryzacyjnych do adresu url
przycisków. Należy pamiętać że przed włączeniem w LMS, należy włączyć logowanie przez "Integration User",
w systemie OltManager.
> omi.olt_manager_automatic_login

(Opcjonalny) Filtrowanie taryf klienta po dniu naliczania. 
Jeśli ustawione i nie jest `null` to `getNodeAccessConfigurationCollection` pobierze wyłącznie taryfy klienta, które
mają ustawione naliczanie na podany dzień. np "1" - naliczanie co miesiąc 1 dnia. Cala reszta zostanie pominięta.
> omi.node_access_configuration_at

(Opcjonalny) Filtrowanie taryf klienta po grupie komputera. 
Jeśli ustawione i nie jest `null` to `getNodeAccessConfigurationCollection` pobierze wyłącznie komputery, które nie są
przypisane żadnej ze wskazanych grup.
Podajemy ID grupy komputera, które chcemy zignorować. Można podać wiele ID grup oddzielając je przecinkiem np. "1,2,3".
> omi.node_access_configuration_ignore_groups

Linki do OltManager w Node, NetDev i Customer otwierają się w nowej karcie
Domyślnie "false", zmiana na true powoduje otwieranie linków do OltManager w nowej karcie
>omi.olt_manager_open_in_new_tab

Dodatkowe parametry, jakie mają być dodawane podczas filtrowania listy Onu.
Domyślnie `?enabled=1`.
>omi.olt_manager_onu_link_params

## Uprawnienia

Do poprawnej pracy OltManager'a należy utworzyć konto w systemie LMS i nadać mu
uprawnienie `omi_full_access`.

Dodatkowym uprawnieniem jest omi_read_only, które pozwala na wejście do sekcji
Urządzenia z błędami, czyli do modułu `omideviceerrorlist`.

Inne uprawnienia:

- `omi_api_data_getter` - pełne uprawnienia do modułu `omiapidatagetter` (API)
- `omi_data_getter` - pełne uprawnienia do modułu `omidatagetter` (API)

Nie jest zalecane nadawanie uprawnienia `omi_full_access`,`omi_api_data_getter`
, `omi_data_getter` nikomu poza kontem do integracji.

### OMI API

Obecnie obsługiwane funkcje:

- `getNetworkDeviceConnections` - pobiera listę NetworkDeviceConnections która jest
  odpowiednikiem encji w OltManager. NetworkDeviceConnection zawiera komputery(device)
  i urządzenia sieciowe(networkDevice) razem z adresem i właścicielem. Wyświetla wyłącznie
  listę z urządzeniami które zostały rozpoznane jako ONU.
- getNetworkDeviceConnectionsWithError - pobiera listę obiektów z błędami. Lista zawiera informacje o błędach
  w obiektach które nie pozwalają na poprawne wygenerowanie NetworkDeviceConnections do integracji z OM. Wykorzystywane
  w module 'omideviceerrorlist'.
- `getMyToken` - pobiera token zalogowanego użytkownika wykorzystywany do autoryzacji OM.
- `getUserTokens` - pobiera tokeny wszystkich włączonych użytkowników systemu.
- `getMyLogin` - pobiera login zalogowanego użytkownika.
- `getPPPoECredentials` - pobiera dane do autoryzacji PPPoE. (Login, hasło)
  Dodatkowe parametry:
    - `mac` - Adres mac urządzenia dla którego pobieramy dane.
        - `upMacs` - Ilość adresów mac powyżej wskazanego które sprawdzać
        - `downMacs` - Ilość adresów mac poniżej wskazanego które sprawdzać

> /?m=omidatagetter&type={functionName}{otherParams}

Przykłady:
> /?m=omidatagetter&type=getNetworkDeviceConnections

> /?m=omidatagetter&type=getPPPoECredentials&mac=78:31:FF:27:FF:9A&upMacs=2&downMacs=2

Podany przykład pobierze dane do autoryzacji PPPoE dla urządzenia o adresie mac 78:31:FF:27:FF:9A.
Jeżeli nie znajdzie, sprawdzi również 2 adresy wyżej oraz 2 adresy niżej aż do natrafienia na sesję PPPoE.
Czyli sprawdzane adresy to:

- `78:31:FF:27:FF:9A`
- `78:31:FF:27:FF:9B`
- `78:31:FF:27:FF:9C`
- `78:31:FF:27:FF:99`
- `78:31:FF:27:FF:98`

{functionName} - nazwa funkcji w klasie LMS.
W ten sposób możliwe jest uruchomienie dowolnej funkcji.
Dodatkowo przez utworzenie Refleksji pobierane są wymagane zmienne z funkcji,
przez co możliwe jest podanie dodatkowych parametrów. Np. można dodać `&argorder=asc`.
Do wykonywanej funkcji zostanie dodany argument 'order' z wartościa `asc`.
Należy pamiętać, że jeżeli w funkcji argument nosi nazwę np. `params` to należy
podać `argparams`. (Wynika to z możliwości z duplikowania podstawowych argumentów API)
(Dodatkowo wspiera wyłącznie proste parametry, co oznacza że przekazywanie tablic
nie jest obecnie możliwe w żadnej formie, ponieważ obecnie jest przekazywana surowa wartość)

### LMS API

> /?m=omiapidatagetter&type={functionName}{otherParams}

Obecnie obsługiwane funkcje:

- `getNodeCollection` - pobiera listę komputerów w systemie LMS.
- `getNodeChecksum` - pobiera sumę kontrolną komputerów w systemie LMS.
- `getNetDevCollection` - pobiera listę urządzeń sieciowych w systemie LMS.
- `getNetDevChecksum` - pobiera sumę kontrolną urządzeń sieciowych w systemie LMS.
- `getNetNodeCollection` - pobiera listę urządzeń głównych w systemie LMS.
- `getNetNodeChecksum` - pobiera sumę kontrolną urządzeń głównych w systemie LMS.
- `getCustomerList` - pobiera listę klientów w systemie LMS.
- `getCustomerChecksum` - pobiera sumę kontrolną klientów w systemie LMS.
- `getTariffsList` - pobiera listę taryf w systemie LMS.
- `getTariffsChecksum` - pobiera sumę kontrolną taryf w systemie LMS.
- `getNodeAccessConfigurationCollection` - pobiera listę taryf komputerów w systemie LMS.
- `getNodeAccessConfigurationCollectionChecksum` - pobiera sumę kontrolną taryf komputerów w systemie LMS.
- `getNodeGroupCollection` - pobiera listę grup komputerów w systemie LMS.
- `getNodeGroupCollectionChecksum` - pobiera sumę kontrolną grup komputerów w systemie LMS.
Przykłady:
> /?m=omiapidatagetter&type=GetNetDevList
> /?m=omiapidatagetter&lmsDirect=1&type=GetNetDevList&argorder=name,desc
