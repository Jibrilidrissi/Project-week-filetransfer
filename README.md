# FileTransfer Applicatie

Een veilige PHP bestandsupload- en deelservice.

## Toegevoegde Functionaliteiten
* **Login & Registratie**: Sessie-beveiligd inlogsysteem met gehashte wachtwoorden.
* **Upload met Limieten**: Bestandsuploads met limieten (bestandstypen en max. grootte) geconfigureerd in `config/config.php`.
* **Eigen overzicht**: Gebruikers zien uitsluitend hun eigen geüploade bestanden.
* **Download via ID & Wachtwoord**: Gebruikers kunnen bestanden downloaden door het ID en wachtwoord in te voeren in plaats van op een directe link te klikken.
* **Admin Dashboard**: Admin gebruiker kan bestanden en gebruikers verwijderen.

## Bestandsstructuur
* `client/`: Login (`index.php`), styling (`style.css`), en script (`script.js`).
* `config/`: Databaseverbinding (`db.php`) en uploadlimieten (`config.php`).
* `server/`: Uploadpagina (`voorpagina.php`).
* `uploads/`: Opslaglocatie van de bestanden.

