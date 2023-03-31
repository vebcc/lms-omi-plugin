# LMS OltManager integration plugin
Wtyczka do systemu LMS realizująca integrację do systemu OltManager.

Ze względu na różne podejście ISP do zapisywania adresów w lmsie,
konieczne było stworzenie wtyczki, która pozwoli wyciągać niezbędne dane z systemu.
"API" lmsa dodatkowo nie pozwala na pobranie wszystkich danych. Na przykład w przypadku
NetDev nie ma możliwości pobrania adresu jako TERYT.

Wtyczka dodatkowo zapewnia możliwość szybkiego przejścia do systemu OlManager
bezpośrednio z Node i NetDev przez dodanie przycisku, który kieruje bezpośrednio
na zsynchronizowane Onu.

## Moduły

- Urządzenia z błędami - wyświetla listę z listą urządzeń, które mają
niepoprawnie skonfigurowane adresy Onu, przez co nie zostaną zsynchronizowane
z systemem OltManager.


Wykona funkcję GetNetDevList i poda do niej parametr order 

## Ustawienia
Do poprawnej pracy wtyczki niezbędne jest dodanie ustawień do systemu

Pełny adres do aplikacji OltManager
>omi.olt_manager_url

Token do autoryzacji z systemu OltManager
W systemie OltManager musi być dodane konto, które ze względów bezpieczeństwa
powinno mieć uprawnienia wyłącznie do integracji LMS. Dodatkowo należy wygenerować
token i wprowadzić go do LMS'a.
>omi.olt_manager_token

Typ dostawcy adresów onu.
Domyślnie i obecnie jedyny wspierany: "description"
>omi.provider_type

## Uprawnienia
Do poprawnej pracy OltManager'a należy utworzyć konto w systemie LMS i nadać mu
uprawnienie 'omi_full_access'.

Dodatkowym uprawnieniem jest omi_read_only, które pozwala na wejście do sekcji
Urządzenia z błędami, czyli do modułu 'omideviceerrorlist'.

Inne uprawnienia:
- 'omi_api_data_getter' - pełne uprawnienia do modułu 'omiapidatagetter' (API)
- 'omi_data_getter' - pełne uprawnienia do modułu 'omidatagetter' (API)

Nie jest zalecane nadawanie uprawnienia 'omi_full_access','omi_api_data_getter'
, 'omi_data_getter' nikomu poza kontem do integracji, ponieważ moduł API
wtyczki pozwala na pełny dostęp do klasy LMS.
Pozwala to na obejście wszystkich innych uprawnień systemowych!!!.

## API
API dzieli się na 2 sekcje. 1 służy do pobierania gotowych danych do systemu OltManager.
2 sekcja pozwala na pobieranie surowych danych w formie json z systemu.
Z parametrem 'lmsDirect=1', dodatkowo pozwala uruchamiać funkcje bezpośrednio
z głównej klasy 'LMS'.
### OMI API
Obecnie obsługiwane funkcje:
    - getNetworkDeviceConnections

>/?m=omidatagetter&type={functionName}{otherParams}

Przykłady:
>/?m=omidatagetter&type=getNetworkDeviceConnections

{functionName} - nazwa funkcji w klasie LMS.
W ten sposób możliwe jest uruchomienie dowolnej funkcji.
Dodatkowo przez utworzenie Refleksji pobierane są wymagane zmienne z funkcji,
przez co możliwe jest podanie dodatkowych parametrów. Np. można dodać &argorder=asc.
Do wykonywanej funkcji zostanie dodany argument 'order' z wartościa 'asc'.
Należy pamiętać, że jeżeli w funkcji argument nosi nazwę np. 'params' to należy
podać 'argparams'. (Wynika to z możliwości z duplikowania podstawowych argumentów API)
(Dodatkowo wspiera wyłącznie proste parametry, co oznacza że przekazywanie tablic
nie jest obecnie możliwe w żadnej formie, ponieważ obecnie jest przekazywana surowa wartość)
### LMS API

>/?m=omiapidatagetter&type={functionName}{otherParams}

W przypadku podania '&lmsDirect=1' są wykonywane funkcje bezpośrednio z klasy
LMS. Bez podania tego parametru wykonywane są funkcje z klasy API wtyczki.

Przykłady:
>/?m=omiapidatagetter&type=GetNetDevList
>/?m=omiapidatagetter&lmsDirect=1&type=GetNetDevList&argorder=name,desc

