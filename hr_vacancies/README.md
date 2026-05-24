# HR Vacancies

Drupal custom module der henter stillingsopslag fra HR Manager og viser dem i en Drupal-blok.

## Installation

1. Aktiver modulet:
   ```bash
   ddev drush en hr_vacancies -y
   ```

2. Ryd cache:
   ```bash
   ddev drush cr
   ```

## Konfiguration

1. Gå til **Struktur > Blok-layout** (`/admin/structure/block`)
2. Klik "Placer blok" i den region, hvor du vil vise stillingsopslag
3. Find "HR Vacancies" og klik "Placer blok"
4. Konfigurer blokindstillingerne og gem

## Funktioner

- Viser stillingsopslag fra **to kildesystemer** i en overgangsperiode (gammel + ny HR Manager)
- **Gammelt system** (iframe): `statens_erekruttering` — vises med klassen `.hr-vacancies-old`
- **Nyt system** (RSS-feed): `statensrekrutteringsloesning_tr` — vises med klassen `.hr-vacancies-new`
  - Henter live fra RSS: `https://recruiter-api.hr-manager.net/jobportal.svc/statensrekrutteringsloesning_tr/positionlist/rss/?depid=20171&incads=1`
  - Viser titel, teaser (første afsnit fra jobopslaget) og publiceringsdato
  - Klikbart link til ansøgningssiden
- Footer med **Tilmeld jobagent** og **Ansøger log ind** under stillingsopslag
- Cache-levetid på 1 time for bedre ydeevne
- Responsivt design uden iframe-begrænsninger (nyt system)

## Overgangsperiode — skjul gammelt system

Når det gamle HR Manager-system (`statens_erekruttering`) er udfaset, kan det skjules
**uden deploy** ved at tilføje en `<style>`-tag direkte i Drupal-editorens HTML-kildekode
på siden `/da/ledige-stillinger` (tekstformat: **Admin no filter**):

```html
<style>.hr-vacancies-old { display: none; }</style>
```

Indsæt dette øverst i sidens body-felt via **Kilde**-knappen i CKEditor.

Når det gamle system er helt udfaset og reglen er tilføjet, kan `buildOldIframe()` i
`VacancyFetcher.php` og kaldet hertil i `fetchVacancies()` slettes i næste deploy.

## API-kilder

| System | Type | URL |
|--------|------|-----|
| Gammelt | iframe | `https://candidate.hr-manager.net/vacancies/list.aspx?customer=statens_erekruttering&departmentid=6139` |
| Nyt | RSS | `https://recruiter-api.hr-manager.net/jobportal.svc/statensrekrutteringsloesning_tr/positionlist/rss/?depid=20171&incads=1` |

## Hvorfor er modulet nødvendigt? (iframe + iframeResizer)

Modulet er nødvendigt på grund af **iframeResizer**-biblioteket og den automatiske højdejustering af iframe'en for det gamle system. Et simpelt `<iframe>`-tag i en skabelon er ikke tilstrækkeligt — her er hvorfor:

### 1. Bundler `iframeResizer.min.js` (host-siden)
[iframeResizer v3](https://github.com/davidjbradshaw/iframe-resizer) kræver **to scripts**:
- **Host-siden** (denne side): `iframeResizer.min.js` — inkluderet i dette modul under `js/`
- **Content-siden** (inde i iframe'en): `iframeResizer.contentWindow.min.js` — HR Manager loader selv dette script

### 2. `hr_vacancies.js` håndterer iframe-oplevelsen
- Initialiserer `iFrameResize()` på iframe-elementet
- Styrer **loading-spinner** med fade-overgang
- Fallback-logik via `load`-event og 3-sekunders timeout

### 3. Drupal asset management via `hr_vacancies.libraries.yml`
- JS/CSS loades kun på sider der renderer blokken
- Korrekt load-rækkefølge og afhængigheder

## Tilpasning

### Styling
```
modules/anklagemyndigheden/hr_vacancies/css/hr_vacancies.css
```

### Skabelon
```
modules/anklagemyndigheden/hr_vacancies/templates/hr-vacancies-block.html.twig
```

Ryd cache efter ændringer:
```bash
ddev drush cr
```

## Fejlfinding

### Ingen stillingsopslag vises (nyt system)
1. Tjek loggen: `/admin/reports/dblog`
2. Søg efter `hr_vacancies`-poster — RSS-fejl logges her
3. Test RSS-feed direkte: `curl 'https://recruiter-api.hr-manager.net/jobportal.svc/statensrekrutteringsloesning_tr/positionlist/rss/?depid=20171&incads=1'`

### Ingen stillingsopslag vises (gammelt system)
1. Tjek at iframe-URL'en stadig er aktiv
2. Kontrollér iframeResizer-konfigurationen i `hr_vacancies.js`

### SSL-certifikatfejl
På udviklings- og testservere kan SSL-verifikation give fejl. På produktion sikres korrekte certifikater.

## Filstruktur

```
hr_vacancies/
├── hr_vacancies.info.yml
├── hr_vacancies.libraries.yml
├── hr_vacancies.module
├── hr_vacancies.services.yml
├── css/
│   └── hr_vacancies.css
├── js/
│   ├── hr_vacancies.js
│   └── iframeResizer.min.js
├── src/
│   ├── Plugin/
│   │   ├── Block/
│   │   │   └── HrVacanciesBlock.php      # Block-plugin
│   │   └── Filter/
│   │       └── HrVacanciesShortcode.php  # Shortcode-filter
│   └── Service/
│       └── VacancyFetcher.php            # iframe (gammelt) + RSS-parser (nyt)
└── templates/
    └── hr-vacancies-block.html.twig      # Skabelon til visning
