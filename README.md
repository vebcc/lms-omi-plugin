# LMS OltManager integration plugin
Wtyczka do systemu LMS realizująca integrację do systemu OltManager.

Ze względu na różne podejście ISP do zapisywania adresów w lmsie,
konieczne było stworzenie wtyczki, która pozwoli wyciągać niezbędne dane z systemu.
"API" lmsa dodatkowo nie pozwala na pobranie wszystkich danych. Na przykład w przypadku
NetDev nie ma możliwości pobrania adresu jako TERYT



## API
Dodatkowo napisana została prosta klasa umożliwiająca pobieranie wszystkich danych,
które można pozyskać z głównej klasy LMS

>/?m=omidatagetter&module=api&lmsDirect=1&type={functionName}

{functionName} - nazwa funkcji w klasie LMS.
W ten sposób możliwe jest uruchomienie dowolnej funkcji.
Dodatkowo przez utworzenie Refleksji pobierane są wymagane zmienne z funkcji,
przez co możliwe jest podanie dodatkowych parametrów. Np. można dodać &order=asc.

>/?m=omidatagetter&module=api&lmsDirect=1&type=GetNetDevList&order=asc

Wykona funkcję GetNetDevList i poda do niej parametr order 