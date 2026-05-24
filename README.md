# Drupal Modules

Samling af custom Drupal 10 moduler og eksperimenter.
---

## Moduler

### 📰 [anklagerupdate_rss](./anklagerupdate_rss/)

Henter og gemmer korte beskeder (short messages) fra AnklagerUpdate RSS-feeds via Ritzau og viser dem på sitet.

**Funktioner:**
- Importerer RSS-feeds fra 20 anklagemyndigheder og statsadvokater
- Gemmer beskeder i lokal databasetabel
- Viser beskeder i Drupal-block med filtrering og paginering
- Eksponerer JSON REST API til ekstern brug
- Kører automatisk via Drupal cron (ca. hvert 60. minut)

**Installation:**
```bash
drush en anklagerupdate_rss
```

---

### 💼 [hr_vacancies](./hr_vacancies/)

Henter stillingsopslag fra HR Manager og viser dem i en Drupal-blok via iframe med automatisk højdejustering.

**Funktioner:**
- Henter stillingsopslag fra HR Manager API
- Viser titel, arbejdssted, ansøgningsfrist, beskrivelse og ansøgningslink
- Automatisk iframe-højdejustering via iframeResizer
- Loading-spinner med fade-out når indhold er klar
- Cache-levetid på 1 time
- Understøtter shortcode-filter

**Installation:**
```bash
drush en hr_vacancies
```

---

### 📧 [nyhedsbrev_ubivox](./nyhedsbrev_ubivox/)

Håndterer nyhedsbrevsabonnementer og automatisk udsendelse via [Ubivox](https://ubivox.com/).

**Funktioner:**
- Tilmeldings- og frameldingsformularer via Drupal-blokke
- Automatisk nyhedsbrevsudsendelse via Ubivox når en artikel publiceres
- Beskytter mod dobbelt-afsendelse
- Admin-notifikation hvis nyhedsbrevet allerede er sendt

**Installation:**
```bash
drush en nyhedsbrev_ubivox
```

> **Kræver:** Ubivox API-biblioteket placeret i `libraries/ubivox-api/` og registreret via Composers `classmap`.

---

### 🧪 [erik_lab](./erik_lab/)

Eksperiment- og proof of concept modul.\

**Demonstrerer:**
- Hooks: `hook_form_alter`, `hook_cron`, `hook_theme`, `hook_preprocess_node`, `hook_node_insert/update/view`
- Custom service (`NodeLister`) med constructor injection og Entity Query
- Controller med ParamConverter / entity upcasting og `#cache`-metadata
- `ConfigFormBase`-formular med `#states`, validering og Config API
- Queue Worker plugin (`LabWorker`) med `#[QueueWorker]`-attribut
- Custom Twig-template (`lab-card.html.twig`)
- PHPUnit Functional- og Kernel-tests
- Behat acceptance tests

**Installation:**
```bash
ddev drush en erik_lab -y && ddev drush cr
```

**Routes:** `/erik-lab/nodes`, `/erik-lab/cache-demo`, `/erik-lab/node/{node}`, `/erik-lab/form`

Se [erik_lab/README.md](./erik_lab/README.md) for fuld dokumentation.

---

## AI Agent instruktioner

Dette projekt indeholder strukturerede instruktionsfiler til AI assistenter
(GitHub Copilot, Claude, Cursor m.fl.).

### `AGENTS.md`

Hoved instruktionsfil til AI agenter. Indeholder:
- Advarsel om newline-bug ved filoprettelse med AI-værktøjer og den verificerede fix kommando
- Projektoverblik (Drupal version, ddev opsætning, default theme, PHP version)
- Ofte brugte ddev/drush-kommandoer
- Projektnoter (lærings-/eksperimentsandkasse, ejer, roadmap)
- Auto genereret `ai-best-practices` blok med links til relevante skill-filer

### `AGENTS_erik.md`

Eriks supplerende instruktioner og kendte faldgruber (lokalt vedligeholdt):
- Behat tests: vejledning om at læse `STEPS.md` før man skriver tests
- Newline bug advarsel med heredoc løsning til nye filer
- PhpStorm inode regel: brug `insert_edit_into_file` til redigering af eksisterende filer
- Projektoverblik og ddev/drush-kommandooversigt

### `.agents/skills/`

Skill filer med domænespecifik viden til AI-agenter:

| Skill | Beskrivelse                                                                                           |
|---|-------------------------------------------------------------------------------------------------------|
| `drupal-expert` | Drupal 10/11 kodestandarder, mønstre og best practices. Læses altid før Drupal PHP/YAML/Twig skrives. |
| `drupal-automated-testing` | Valg af testtype (Functional/Kernel/Unit/FunctionalJavascript), PHPUnit konventioner, faldgruber.     |
| `drupal-writing-documentation` | Vejledning i at skrive README'er, change records, API docblocks og drupal.org dokumentation.          |
| `drupal-accessibility` | Dispatcher til a11y underskills: FAPI, DOM/CSS, dynamisk JS/AJAX og QA/issue-skrivning.               |

---

## Konfiguration og lokalt miljø

### `erik_lab/behat.yml`

Behat konfiguration til acceptance tests. Indeholder miljøspecifikke værdier der **ikke** må overskrives med rigtige credentials i versionsstyring.

| Indstilling | Beskrivelse                                                                      |
|---|----------------------------------------------------------------------------------|
| `base_url` | URL til det lokale ddev site (`https://drupal10.ddev.site`). Justér til dit miljø. |
| `drupal_root` | Absolut sti til Drupal roden **inde i containeren** (`/var/www/html`).           |
| `api_driver: drupal` | Bootstrapper Drupal direkte kræver ingen adgangskode i konfigurationsfilen.      |

**Sikkerhed / passwords:**
Behat-konfigurationen indeholder i sin nuværende form ingen adgangskoder.
Hvis du tilføjer credentials (f.eks. til `blackbox`-driveren eller ekstern grundurl), skal du:

1. Oprette en lokal override-fil: `behat.local.yml` (arver `behat.yml` via `imports`).
2. Tilføje `behat.local.yml` til `.gitignore` — **commit aldrig passwords til git**.
3. Køre Behat med: `ddev exec vendor/bin/behat --config behat.local.yml`

```yaml
# behat.local.yml — ALDRIG commit til git
imports:
  - behat.yml
default:
  extensions:
    Drupal\MinkExtension:
      base_url: https://dit-lokale-site.ddev.site
```

---

## Krav

- Drupal 10+
- PHP 8.1+
- Drush
- ddev (lokalt udviklingsmiljø)

---

## Licens

Intern brug – ikke til offentlig distribution.
