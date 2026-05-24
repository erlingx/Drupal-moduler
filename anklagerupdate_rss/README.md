# AnklagerUpdate RSS Importer

Drupal 10 custom module der henter og gemmer korte beskeder (short messages) fra AnklagerUpdate RSS-feeds via Ritzau og viser dem på sitet.

## Funktioner

- Henter RSS-feeds fra 20 anklagemyndigheder og statsadvokater via `via.ritzau.dk`
- Gemmer beskeder i en lokal databasetabel (`anklagerupdate_messages`)
- Opdaterer eksisterende beskeder ved genimport
- Viser beskeder i en Drupal block med filtrering pr. udgiver og paginering
- Eksponerer en JSON REST API til ekstern brug
- Kører automatisk via Drupal cron (ca. hvert 60. minut)
- Kan køres separat via `run-rss-import.php` (anbefalet: hvert 10. minut)

## Installation

1. Aktiver modulet:
   ```
   drush en anklagerupdate_rss
   ```
2. Kør databasemigrering (oprettes automatisk ved aktivering via `hook_schema`).

## Databasetabel: `anklagerupdate_messages`

| Felt            | Type    | Beskrivelse                        |
|-----------------|---------|------------------------------------|
| `id`            | serial  | Primærnøgle                        |
| `guid`          | varchar | Unikt RSS GUID (unik nøgle)        |
| `publisher_id`  | varchar | Udgiverens ID (eks. `13563078`)    |
| `publisher_name`| varchar | Udgiverens navn                    |
| `title`         | varchar | Beskedens overskrift               |
| `description`   | text    | Beskedens indhold                  |
| `link`          | varchar | Link til fuld besked               |
| `pub_date`      | int     | Publiceringsdato (Unix timestamp)  |
| `category`      | varchar | Kategori                           |
| `created`       | int     | Oprettelsestidspunkt i DB          |
| `updated`       | int     | Sidst opdateret i DB               |

## REST API

### GET `/api/anklagerupdate/messages`

Returnerer paginerede beskeder.

**Query-parametre:**

| Parameter      | Beskrivelse                                  | Standard |
|----------------|----------------------------------------------|----------|
| `publisher_id` | Filtrer på udgiver-ID                        | –        |
| `page`         | Sidenummer                                   | `1`      |
| `limit`        | Antal pr. side (maks. 100)                   | `10`     |
| `search`       | Fritekstsøgning i titel og beskrivelse       | –        |

**Eksempel:**
```
/api/anklagerupdate/messages?publisher_id=13563078&page=1&limit=20
```

### GET `/api/anklagerupdate/publishers`

Returnerer liste over alle udgivere med antal beskeder og dato for seneste besked.

## Block

Blokkens plugin-ID er `anklagerupdate_messages_block` (admin label: *AnklagerUpdate Messages*).  
Kan placeres via Drupal block layout. Understøtter URL-parametre `publisher_id` og `page` til filtrering og paginering. Cache-levetid er 5 minutter.

## Udgivere

Følgende 20 udgivere importeres:

| ID         | Navn                                                                 |
|------------|----------------------------------------------------------------------|
| 13563078   | Anklagemyndigheden                                                   |
| 13563094   | Anklagemyndigheden ved Bornholms Politi                              |
| 13563088   | Anklagemyndigheden ved Fyns Politi                                   |
| 13563097   | Anklagemyndigheden ved Færøernes Politi                              |
| 13563096   | Anklagemyndigheden ved Grønlands Politi                              |
| 13563092   | Anklagemyndigheden ved Københavns Politi                             |
| 13563091   | Anklagemyndigheden ved Københavns Vestegns Politi                    |
| 13563084   | Anklagemyndigheden ved Midt- og Vestjyllands Politi                  |
| 13563089   | Anklagemyndigheden ved Midt- og Vestsjælland                         |
| 13563095   | Anklagemyndigheden ved National enhed for Særlig Kriminalitet        |
| 13563083   | Anklagemyndigheden ved Nordjyllands Politi                           |
| 13563093   | Anklagemyndigheden ved Nordsjællands Politi                          |
| 13563087   | Anklagemyndigheden ved Syd- og Sønderjyllands Politi                 |
| 13563090   | Anklagemyndigheden ved Sydsjællands og Lolland-Falsters Politi       |
| 13563086   | Anklagemyndigheden ved Sydøstjyllands Politi                         |
| 13563085   | Anklagemyndigheden ved Østjyllands Politi                            |
| 13563079   | Rigsadvokaten                                                        |
| 13563082   | Statsadvokaten for Særlig Kriminalitet                               |
| 13563080   | Statsadvokaten i København                                           |
| 13563081   | Statsadvokaten i Viborg                                              |

## Test-seed script (`seed-test-messages.php`)

Placeret i projektets rod. Indsætter falske beskeder direkte i databasen uden at kalde det rigtige RSS-feed. Kan kun køres på `test` og lokal DDEV — **ikke på `prd`**.

### Miljøer

Scriptet kan **ikke** køre på PRD. Det understøtter to miljøer:

| Miljø | Hvor køres det | Terminal | Kommando |
|-------|---------------|----------|----------|
| `ddev` | Lokal udvikling | PowerShell/bash på din maskine | `ddev php seed-test-messages.php` |
| `test` | Remote TEST-server (31.31.83.25) | RDP-terminal på TEST-serveren | `"C:\Program Files\PHP\v8.2.12\php.exe" seed-test-messages.php test` |

> **Vigtigt på TEST-serveren:** Standard `php`-kommandoen er v7.1 og virker ikke. Brug altid den fulde sti til PHP 8.2: `"C:\Program Files\PHP\v8.2.12\php.exe"`

Miljøet auto-detekteres: inde i DDEV bruges `ddev` automatisk, så argumentet kan udelades.

### Brug

```bash
# På lokal DDEV (auto-detekterer miljø)
ddev php seed-test-messages.php

# På TEST-serveren (via RDP-terminal) – brug altid PHP 8.2
"C:\Program Files\PHP\v8.2.12\php.exe" seed-test-messages.php test

# Slet alle testbeskeder igen (DDEV)
ddev php seed-test-messages.php --clear

# Slet alle testbeskeder igen (TEST)
"C:\Program Files\PHP\v8.2.12\php.exe" seed-test-messages.php test --clear
```

### Hvad scriptet indsætter

3 beskeder fra 3 forskellige udgivere:

| GUID-præfiks | Udgiver                     | Kategori          | pub_date         |
|--------------|-----------------------------|-------------------|------------------|
| `TEST-1-…`   | Anklagemyndigheden          | Pressemeddelelse  | nu − 10 minutter |
| `TEST-2-…`   | Rigsadvokaten               | Orientering       | nu − 5 minutter  |
| `TEST-3-…`   | Statsadvokaten i København  | Afgørelse         | nu (nyeste)      |

### Sådan bruges de tidsforskudte pub_dates som tests

`pub_date` sættes relativt til det tidspunkt scriptet køres:

- **`$now - 600`** (10 min. siden) — tester at ældre beskeder sorteres korrekt bag nyere
- **`$now - 300`** (5 min. siden) — tester mellemposition i sorteringen
- **`$now`** — tester at den nyeste besked vises øverst

Blokken viser beskeder sorteret efter `pub_date DESC`, så de tre rækker bør fremgå i rækkefølgen: Statsadvokaten → Rigsadvokaten → Anklagemyndigheden. Hvis rækkefølgen er forkert, er der en fejl i sorteringen.

De tidsforskudte værdier gør det også muligt at verificere, at **filtrering pr. udgiver** virker uafhængigt af tidspunktet — alle tre udgivere har bevidst forskellig `pub_date` så ingen tilfældigt kolliderer.

### Test af cache-invalidering (kun TEST-serveren)

DDEV har cache slået fra, så cache-invalidering kan kun testes på TEST-serveren (31.31.83.25) via RDP-terminal.

**Testflow:**

```cmd
# 1. Besøg siden som anonym bruger → siden caches
#    http://31.31.83.25/nyheder (eller relevant sti)

# 2. Indsæt testbeskeder UDEN at invalidere cachen
"C:\Program Files\PHP\v8.2.12\php.exe" seed-test-messages.php test --no-invalidate

# 3. Genindlæs siden som anonym → de nye beskeder vises IKKE (korrekt – siden er cached)

# 4. Kør RSS-importeren – den kalder Cache::invalidateTags() internt
"C:\Program Files\PHP\v8.2.12\php.exe" run-rss-import.php test

# 5. Genindlæs siden → de nye beskeder vises nu ✅

# 6. Ryd op
"C:\Program Files\PHP\v8.2.12\php.exe" seed-test-messages.php test --clear
```

**Hvad testen verificerer:**
- At siden ikke opdateres ved direkte DB-indsættelse (cachen virker)
- At `run-rss-import.php` invaliderer cachen korrekt
- At anonyme brugere ser friske data efter næste kørsel af importeren

### Oprydning

`--clear` sletter kun rækker med `guid LIKE 'TEST-%'`, så rigtige data ikke berøres.

Alle handlinger logges til Drupal watchdog under kanalen `anklagerupdate_rss` med `env`-parameteren.

## Tekniske noter

- SSL-verifikation er deaktiveret på produktions- og testservere (Windows) pga. certifikatproblemer. Ændres i `anklagerupdate_rss.module` i `_anklagerupdate_rss_fetch_and_store()`.
- RSS-feed-URL: `https://via.ritzau.dk/rss/short-messages/latest?publisherId={ID}`
- Fejl logges via Drupal watchdog under kanalen `anklagerupdate_rss`.

## Filstruktur

```
anklagerupdate_rss/
├── anklagerupdate_rss.info.yml
├── anklagerupdate_rss.install          # Databaseskema
├── anklagerupdate_rss.module           # hook_cron, hook_theme, import-logik
├── anklagerupdate_rss.routing.yml      # API-ruter
├── src/
│   ├── Controller/
│   │   └── ApiController.php           # REST API endpoints
│   └── Plugin/Block/
│       └── AnklagerUpdateMessagesBlock.php
└── templates/
    └── anklagerupdate-messages-block.html.twig
```

