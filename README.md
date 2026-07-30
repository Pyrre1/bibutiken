# Bibutiken

Butikssida för **Strängnäs Biredskap AB** med verktyg som gör det möjligt för butiksägaren att själv administrera delar av webbplatsen, planera öppettider, hantera förbeställningar och kommunicera med kunder.

---
**Status:** Aktivt sommarprojekt under utveckling.
> Koden och funktionaliteten utvecklas löpande och README:n uppdateras i takt med projektet.
---
# Bakgrund

Efter kontakt med butiksägaren tog jag mig an projektet som ett sommarprojekt. Målet var både att leverera en lösning åt kunden och att fördjupa mina kunskaper genom ett verkligt utvecklingsprojekt.

Kunden ville minska beroendet av Facebook för att kommunicera öppettider och istället kunna uppdatera dem direkt på den egna webbplatsen. Samtidigt fanns ett behov av att centralisera förbeställningsflödet för vinterfoder och skapa en bättre upplevelse för både kunder och administratör.

---

# Omfattning

Projektet växte successivt under utvecklingen. Eftersom den första MVP-versionen färdigställdes tidigare än planerat fanns utrymme att vidareutveckla systemet med fler funktioner och samtidigt ersätta flera tillfälliga lösningar med mer genomarbetade och framtidssäkra implementationer.

## MVP

### Publik sida

- Dynamiska öppettider hämtade från databas.
- Centraliserat förbeställningsformulär för vinterfoder.
- Möjlighet att exportera kundlistor för manuella e-postutskick.

### Admin

- Överblick över mottagna beställningar.
- Planering av öppettider.
- Orderhantering.

---

## Utökad funktionalitet

Under projektets gång utvecklades flera funktioner utöver den ursprungliga planen.

### Förbeställningar

- Dynamiska produkter i förbeställningsformuläret.
- CRUD-gränssnitt för administration av produkter.
- Separat administration för försäljning av produkter från den egna bigården.

### Kommunikation

Det som från början var tänkt som en enkel CSV-export utvecklades till ett komplett kommunikationsverktyg.

Funktionaliteten omfattar idag bland annat:

- Automatiska bekräftelsemejl till kunden.
- Utskick till alla kunder som väntar på att deras produkter ska anlända.
- Påminnelseutskick med tidsstyrning för att undvika dubbla utskick.
- Rollbaserade mottagargrupper.

Nästa steg är ett mallsystem där administratören själv kan skapa, redigera och återanvända e-postmallar med variabler, exempelvis:

```text
Hej {namn}!

Information som är aktuell för de som har rollerna som får detta meddelande...
```

Mallarna ska kunna skickas till valfria kombinationer av mottagargrupper utan att någon kod behöver ändras. Och en förenklad variabelknapp kan placera ut variabler som {namn} som renderas baserat på mottagare.

### Övrigt

- Banner/notiser med administration.
- Flera tidigare "quick fixes" ersatta med mer långsiktiga lösningar.

---

# Projektets omfattning

**Nedlagd tid:** cirka 6 veckor.

Arbetet utfördes huvudsakligen måndag–fredag med både halvdagar och heldagar beroende på väder och familjeliv.

---

# Teknikstack

## Backend

- PHP *(första större projektet utvecklat i PHP)*
- MariaDB

## Frontend

- HTML
- CSS
- JavaScript

## Lokal utvecklingsmiljö

- Visual Studio Code
- Docker
- Localhost
- Simulerad SMTP-server för e-posttester

## Testmiljö

- Raspberry Pi
- Docker
- NGINX

## Produktionsmiljö

- one.com (delad hosting)

*Driftsättning mot produktionsmiljön pågår.*

---

# Fokusområden

- PHP i MVC-struktur
- Databasdesign
- MariaDB
- CRUD-administration
- Formulärhantering
- Dynamiska öppettider
- E-postutskick
- Docker för lokal utveckling
- Anpassning för delad hosting (one.com)
- GDPR-anpassad hantering av kunduppgifter
- Säker formulärhantering (CSRF, validering, spam-skydd)

---

# Lärdomar

Projektet gav flera värdefulla erfarenheter, både tekniska och praktiska.

## Kundkommunikation

En av de största lärdomarna var vikten av att skapa en gemensam förståelse för krav och förväntningar när kunden saknar teknisk bakgrund. Det räcker inte att en lösning fungerar tekniskt – den måste även motsvara det kunden faktiskt vill uppnå.

## Prioritering

Flera funktioner hann påbörjas men prioriterades senare bort när de visade sig tillföra begränsat värde i den färdiga lösningen. Projektet blev därför också en övning i att prioritera utvecklingstid utifrån faktisk nytta.

## Kodstruktur

Redan efter en veckas uppehåll märktes värdet av en tydlig projektstruktur och kontinuerlig refaktorering. En välorganiserad kodbas gjorde det betydligt enklare att snabbt återuppta utvecklingen.

## Arkitektur

Projektet blev även en praktisk övning i att bygga ett system som kan växa över tid. Flera delar har därför utformats med återanvändbarhet i åtanke, exempelvis administrationsgränssnitt, CRUD-funktionalitet och e-postsystemet, vilket gör att nya funktioner kan byggas ovanpå den befintliga strukturen istället för att kräva speciallösningar.

---

# Status

Projektet är fortfarande under aktiv utveckling.

Nuvarande fokus ligger på:

- E-postmallar med variabler.
- Fler rollbaserade utskick.
- Slutförande av deployment mot one.com.
- Ytterligare förbättringar av administrationsgränssnittet.