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

## API
Dodatkowo napisana została prosta klasa umożliwiająca pobieranie wszystkich danych,
które można pozyskać z głównej klasy LMS

>/?m=omidatagetter&module=api&lmsDirect=1&type={functionName}

{functionName} - nazwa funkcji w klasie LMS.
W ten sposób możliwe jest uruchomienie dowolnej funkcji.
Dodatkowo przez utworzenie Refleksji pobierane są wymagane zmienne z funkcji,
przez co możliwe jest podanie dodatkowych parametrów. Np. można dodać &order=asc.

>/?m=omidatagetter&module=api&lmsDirect=1&type=GetNetDevList&order=asc