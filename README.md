# FileTransfer Applicatie

Een veilige PHP bestandsupload- en deelservice.

## Toegevoegde Functionaliteiten
* **Login & Registratie**: Sessie-beveiligd inlogsysteem met gehashte wachtwoorden.
* **Upload met Limieten**: Bestandsuploads met limieten (bestandstypen en max. grootte) geconfigureerd in `config/config.php`.
* **Eigen overzicht**: Gebruikers zien uitsluitend hun eigen geüploade bestanden.
* **Deellinks**: Unieke links per bestand om direct en veilig te delen met anderen (`download.php`).

## Bestandsstructuur
* `client/`: Login (`index.php`), styling (`style.css`), en script (`script.js`).
* `config/`: Databaseverbinding (`db.php`) en uploadlimieten (`config.php`).
* `server/`: Uploadpagina (`voorpagina.php`) en downloadservice (`download.php`).
* `uploads/`: Opslaglocatie van de bestanden.

