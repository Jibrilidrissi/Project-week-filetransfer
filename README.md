# FileTransfer Applicatie

Een veilige PHP bestandsupload- en deelservice.

## Toegevoegde functionaliteiten

* **Login & Registratie:** Sessie-beveiligd inlogsysteem met gehashte wachtwoorden.
* **Upload met limieten:** Bestandsuploads met limieten voor bestandstypen en maximale grootte. Dit staat in `config/config.php`.
* **Eigen overzicht:** Gebruikers zien uitsluitend hun eigen geüploade bestanden.
* **Deellinks:** Unieke links per bestand om direct en veilig te delen met anderen via `download.php`.
* **Integriteitscontrole:** Bij upload wordt een SHA-256 hash van het bestand gemaakt. Bij download wordt deze hash opnieuw gecontroleerd. Als het bestand is aangepast, stopt de download.


## Bestandsstructuur

* `client/`: Login (`index.php`), styling (`style.css`) en script (`script.js`).
* `config/`: Databaseverbinding (`db.php`) en uploadlimieten (`config.php`).
* `server/`: Uploadpagina (`voorpagina.php`) en downloadservice (`download.php`).
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
