# FileTransfer Applicatie

Een veilige PHP bestandsupload- en deelservice.

## Toegevoegde functionaliteiten

* **Login & Registratie:** Sessie-beveiligd inlogsysteem met aparte rollen voor gebruikers en admins. Sessie-ID's worden na inloggen vernieuwd om session fixation te voorkomen.
* **Upload met limieten:** Bestandsuploads met limieten voor bestandstypen en maximale grootte. Dit staat in `config/config.php`.
* **Drag & drop upload:** Bestanden kunnen via een drag and drop zone worden geselecteerd, met clientside validatie van type en grootte voor upload.
* **Upload met voortgang:** Asynchrone upload via XMLHttpRequest met een realtime voortgangsbalk en statusmeldingen.
* **Bestandsbeveiliging:** Elk geüpload bestand krijgt een verplicht wachtwoord (gehasht opgeslagen) dat nodig is om te downloaden.
* **Uniek File ID:** Bij elke upload wordt automatisch een uniek 5 cijferig bestands-ID (10000–99999) gegenereerd om bestanden te delen.
* **Beschrijving bij upload:** Optioneel veld om een beschrijving of notitie aan een bestand toe te voegen.
* **Tabbladen:** Dashboard met drie secties Secure Upload, Secure Download en Upload History.
* **Secure Download:** Downloaden via bestands-ID en wachtwoord, zonder dat de ontvanger een account nodig heeft.
* **Upload History:** Overzicht van alle eigen uploads met bestands-ID, beschrijving, uploaddatum en kopieerknoppen voor ID en wachtwoord.
* **Eigen overzicht:** Gebruikers zien uitsluitend hun eigen geüploade bestanden.
* **Integriteitscontrole:** Bij upload wordt een SHA256 hash van het bestand gemaakt. Bij download wordt deze hash opnieuw gecontroleerd. Als het bestand is aangepast, stopt de download.
* **Admin Panel:** Admins hebben een apart dashboard (`admin.php`) met statistieken (aantal gebruikers, bestanden, schijfgebruik), gebruikersbeheer (verwijderen inclusief bijbehorende bestanden) en bestandsbeheer (verwijderen van alle uploads).
* **Responsieve interface:** Sidebar-navigatie met mobiel menu, tabbladen en toast-meldingen voor kopieeracties.
* **Uitloggen:** Gebruikers kunnen veilig uitloggen; de sessie wordt volledig beëindigd.


## Bestandsstructuur

* `client/`: Login (`index.php`), styling (`style.css`) en script (`script.js`).
* `config/`: Databaseverbinding (`db.php`) en uploadlimieten (`config.php`).
* `server/`: Uploadpagina (`voorpagina.php`), download-endpoint (`download.php`) en admin-dashboard (`admin.php`).
* `uploads/`: Opslaglocatie van de bestanden.
* `sql/`: Databasebestand of SQL-structuur.

## Gebruikte technieken

* PHP
* MySQL
* HTML
* CSS
* XAMPP
* GitHub

## Beveiliging

De applicatie gebruikt een login- en registratiesysteem. Wachtwoorden worden niet als gewone tekst opgeslagen, maar gehasht.

Bij het uploaden controleert het systeem het bestandstype en de grootte van het bestand. Ook wordt er een SHA-256 hash opgeslagen in de database. Bij het downloaden wordt opnieuw gecontroleerd of het bestand nog hetzelfde is.
