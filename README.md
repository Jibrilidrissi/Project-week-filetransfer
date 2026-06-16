Secure File Transfer System
Project beschrijving

Dit project is een veilig bestandstransfersysteem. Met dit systeem kan een gebruiker bestanden uploaden en downloaden. Het doel van het project is niet alleen dat uploaden en downloaden werkt, maar ook dat er basisbeveiliging is toegevoegd.

Wat kan het systeem?
Bestand uploaden
Bestand downloaden
Lijst met beschikbare bestanden tonen
Bestandstype controleren
Bestandsgrootte controleren
Bestandsinformatie opslaan in MySQL
MD5 hash maken voor integriteitscontrole
Controleren of een bestand veranderd is voor download
Gebruikte technieken
PHP
MySQL
HTML
CSS
XAMPP
GitHub
Projectstructuur
secure-file-transfer/
│
├── client/
│ ├── index.php
│ └── style.css
│
├── server/
│ ├── upload.php
│ └── download.php
│
├── config/
│ ├── config.php
│ └── db.php
│
├── sql/
│ └── database.sql
│
└── uploads/
Database

De bestanden zelf worden niet opgeslagen in de database. De echte bestanden staan in de uploads-map. In de database slaan wij alleen de informatie van het bestand op.

Voorbeelden van informatie:

Originele bestandsnaam
Opgeslagen bestandsnaam
Bestandstype
Bestandsgrootte
Bestandspad
MD5 hash
Beveiliging

Voor de basisbeveiliging controleren wij het bestandstype en de bestandsgrootte. Hierdoor kunnen niet zomaar alle bestanden worden geüpload.

Ook gebruiken wij een MD5 hash. Dit is geen encryptie, maar een simpele integriteitscontrole. Bij upload maken wij een MD5 hash van het bestand. Bij download maken wij opnieuw een MD5 hash. Als de hash anders is, betekent dit dat het bestand is aangepast. Dan stopt de download.

Voor lokale ontwikkeling gebruiken wij localhost. Voor een echte online versie moet het systeem via HTTPS draaien.
