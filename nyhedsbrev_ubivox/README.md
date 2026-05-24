# Nyhedsbrev Ubivox

Drupal custom module til håndtering af nyhedsbrevsabonnementer og -udsendelser via [Ubivox](https://ubivox.com/).

## Funktioner

- Tilmelding og frameldning til nyhedsbreve via Drupal-formularer og -blokke
- Automatisk afsendelse af nyhedsbrev via Ubivox når en `forsite_article`-node publiceres
- Understøtter flere abonnementslister (produktion og test)
- Beskytter mod dobbelt-afsendelse via `newsletters_sent`-tabellen
- Admin-notifikation i node-redigeringsformularen hvis nyhedsbrevet allerede er sendt

## Installation

1. Installer Ubivox API-biblioteket i `/libraries/ubivox-api/ubivox_api.php`
2. Aktiver modulet:
   ```
   drush en nyhedsbrev_ubivox
   ```
3. Konfigurer Ubivox-legitimationsoplysninger under **Administer → Konfiguration** eller direkte i databasen:
   - `ubivox_username`
   - `ubivox_password`
   - `ubivox_url`

## Konfiguration

Indstillingerne gemmes i `nyhedsbrev_ubivox.settings` (config):

| Nøgle              | Beskrivelse                                          |
|--------------------|------------------------------------------------------|
| `ubivox_username`  | Ubivox brugernavn (eks. `rigsadvokaten`)             |
| `ubivox_password`  | Ubivox adgangskode                                   |
| `ubivox_url`       | Ubivox XMLRPC-URL (eks. `https://rigsadvokaten.clients.ubivox.com/xmlrpc/`) |

## Ubivox-lister

| Liste                                          | Prod. ID | Test ID |
|------------------------------------------------|----------|---------|
| Landsdækkende nyheder fra anklagemyndigheden   | 48947    | 64327   |
| Årsrapporter, bekendtgørelser og publikationer | 48997    | 64271   |

Miljø bestemmes automatisk ud fra `HTTP_HOST`. Produktion køres på `anklagemyndigheden.dk`, alt andet anses som test/localhost.

## Udsendelsesflow

1. Redaktør opretter eller redigerer en `forsite_article`-node
2. Redaktøren vælger abonnementsliste(r) i `field_send_mail_to_subscription_`
3. Redaktøren sætter `field_dont_send_email_subscriber` til `1` (checkbox "Send nyhedsbrev")
4. Node gemmes og publiceres
5. `hook_node_presave` / `hook_node_insert` udløses og sender nyhedsbrevet via Ubivox
6. Afsendelse registreres i `newsletters_sent`-tabellen

**Nyhedsbreve kan kun sendes én gang per node.** Forsøg på gen-afsendelse giver en advarsel til redaktøren.

## Formularer og ruter

| Rute                          | Sti               | Formular                       |
|-------------------------------|-------------------|--------------------------------|
| `nyhedsbrev_ubivox.unsubscribe` | `/frameld-nyheder` | `UnsubcribeForm`              |

Tilmeldingsformularen (`SubcribeForm`) eksponeres via blokken `SubscribeBlock` og kan placeres via Drupal block layout.

## Blok

Plugin-ID: `SubscribeBlock` (admin label: *SubscribeBlock*).  
Blokken renderer tilmeldingsformularen med følgende felter:

- E-mailadresse (påkrævet)
- Navn (påkrævet)
- Organisation (valgfri)
- Checkbokse for abonnementslister (mindst én skal vælges)

## Afhængigheder

- `libraries/ubivox-api/ubivox_api.php` – Ubivox PHP API-klient (ikke inkluderet i modulet)
- Drupal core: `views`, `node`

ddev start
## Indlæsning af Ubivox API-bibliotek via Composer

Ubivox API-filerne er ikke et Composer-pakke, men indlæses via en `classmap`-autoload-definition i rodens `composer.json`. Det sikrer, at `UbivoxAPI`- og `IXR_Library`-klasserne er tilgængelige globalt via Composers autoloader.

Tilføj følgende til `composer.json` (rodens, ikke modulets):

```json
"autoload": {
    "classmap": [
        "libraries/ubivox-api/IXR_Library.php",
        "libraries/ubivox-api/ubivox_api.php"
    ]
}
```

Efter ændringen skal autoloaderen regenereres:

```
composer dump-autoload
```

Filerne skal placeres i:
```
libraries/
└── ubivox-api/
    ├── IXR_Library.php
    └── ubivox_api.php
```

## Logging

Alle hændelser logges via Drupal watchdog under kanalen `nyhedsbrev_ubivox`:

- Tilmeldinger og frameldinger
- Nyhedsbreve oprettet og sendt i Ubivox
- Fejl fra Ubivox API (`UbivoxAPIError`, `UbivoxAPIException`)
- Advarsel ved forældede noder (nid ≤ 848)

## Filstruktur

```
nyhedsbrev_ubivox/
├── nyhedsbrev_ubivox.info.yml
├── nyhedsbrev_ubivox.libraries.yml
├── nyhedsbrev_ubivox.module           # hook_node_presave, hook_node_insert, hook_form_alter
├── nyhedsbrev_ubivox.routing.yml      # Frameldingsrute
├── config/
│   ├── install/
│   │   └── nyhedsbrev_ubivox.settings.yml
│   └── schema/
│       └── nyhedsbrev_ubivox.schema.yml
├── css/
│   └── nyhedsbrev_ubivox.css
├── js/
│   └── nyhedsbrev_ubivox.js
└── src/
    ├── Form/
    │   ├── SubcribeForm.php            # Tilmeldingsformular
    │   ├── SubcribeForm_array.php
    │   ├── SubcribeForm_optin.php
    │   └── UnsubcribeForm.php          # Frameldingsformular
    └── Plugin/Block/
        └── SubscribeBlock.php          # Block der renderer tilmeldingsformularen
```

