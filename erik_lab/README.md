# Erik Lab

Drupal eksperimenter, proof of concept og test af moduler.

## Aktivér

```bash
ddev drush en erik_lab -y && ddev drush cr
```

---

## Filer

### `erik_lab.module` — hooks

| Hook | Hvad det demonstrerer                                                                                                                                 |
|---|-------------------------------------------------------------------------------------------------------------------------------------------------------|
| `hook_form_alter` | Rammer `node_page_form` og omdøber title feltet. Besøg `/node/add/page`. Alle formID'er logges via `\Drupal::logger()`.                               |
| `hook_cron` | Gemmer tidsstempel for seneste kørsel i **State API**. Sender job til `erik_lab_queue` i stedet for at gøre det inlinem hvilket er den korrekte måde. |
| `hook_theme` | Registrerer `lab_card` Twig-template med tre variabler (`title`, `body`, `extra_class`).                                                              |
| `hook_preprocess_node` | Injicerer `{{ erik_lab_note }}` i alle node-templates.                                                                                                |
| `hook_node_insert` | Invaliderer manuelt `node_list`- og `node:<nid>` cache-tags ved oprettelse af ny node.                                                                |
| `hook_node_update` | Invaliderer `node:<nid>` ved opdatering.                                                                                                              |
| `hook_node_view` | Tilføjer et cachebart render element til alle node visninger og demonstrerer alle tre `#cache` keys: `tags`, `contexts`, `max-age`.                   |

### `src/Service/NodeLister.php`

Custom service (`erik_lab.node_lister`). Demonstrerer:
- Constructor injection (`EntityTypeManagerInterface`, `LoggerChannelFactoryInterface`)
- `entityQuery` → `loadMultiple` mønsteret (SQL frit, access checket)
- Feltværdi adgang: scalar, lang tekst, entity reference, multi value
- Defineret i `erik_lab.services.yml`, injiceret i `LabController`

### `src/Controller/LabController.php`

Extender `ControllerBase`. Demonstrerer:
- Constructor DI med `create()` factory
- Render arrays med fuld `#cache` metadata
- `item_list` og `table` render elementtyper
- **ParamConverter / entity upcasting**: `{node}` i route-stien loades automatisk som `NodeInterface` Intet manuelt `load()`kald nødvendigt. Manglende NID → automatisk 404.

### `src/Form/LabForm.php`

Extender `ConfigFormBase`. Demonstrerer:
- `#type`: `textfield`, `checkbox`  læser/skriver `erik_lab.settings`
- `#states`: `log_channel` feltet vises kun når checkbox er afkrydset (rent JS, intet custom JS nødvendigt)
- `validateForm()` med `setErrorByName()` (fremhæver det specifikke felt)
- `submitForm()` skriver til Config API → eksporterbart via `drush cex`

### `src/Plugin/QueueWorker/LabWorker.php`

PHP attribut `#[QueueWorker]` plugin. Demonstrerer:
- Plugin DI via `ContainerFactoryPluginInterface`
- `processItem()`: returner normalt = succes (item slettes), kast exception = retry, kast `SuspendQueueException` = stop hele queue kørslen
- `cron: ['time' => 30]`  maks. sekunder cron bruger på denne queue pr. kørsel

### `templates/lab-card.html.twig`

Custom Twig template registreret af `hook_theme`. Variabler: `title`, `body`, `extra_class`.

### `config/install/erik_lab.settings.yml`

Standardkonfiguration importeret ved `drush en`. Indeholder: `greeting`, `enable_logging`, `log_channel`.

### `config/schema/erik_lab.schema.yml`

Config schema for `erik_lab.settings`. Påkrævet for config-validering og oversættelse. Mapper hver nøgle til en typed data type.

---

## Routes

| Sti | Controller method          | Rettighed | Hvad den viser                                                                |
|---|----------------------------|---|-------------------------------------------------------------------------------|
| `/erik-lab/nodes` | `LabController::nodeList`  | `access content` | Entity API forespørgsel → tabel-render array med `node_list` cache tag        |
| `/erik-lab/cache-demo` | `LabController::cacheDemo` | `access content` | `config:system.site` cache tag, `url.path` kontekst, 60 sek. max age          |
| `/erik-lab/node/{node}` | `LabController::nodeView`  | `access content` | ParamConverter upcasting  `{node}` → `NodeInterface`. Prøv `/erik-lab/node/1` |
| `/erik-lab/form` | `LabForm`                  | `administer site configuration` | ConfigFormBase, `#states`, validering, Config API save                        |
| `/node/add/page` | *(core)*                   | — | Se `hook_form_alter` omdøbe title feltets label                              |

---

## Drush

```bash
ddev drush cr                                     # gencache rebuild
ddev drush cron                                   # kør Drupal cron
ddev drush queue:list                             # Antal items i erik_lab_queue
ddev drush queue:run erik_lab_queue               # Kør queue (kører LabWorker::processItem)
ddev drush cex                                    # eksportér → config/sync/erik_lab.settings.yml
ddev drush watchdog:show --type=erik_lab          # alle logposter fra erik_lab
ddev drush php:eval "\Drupal::state()->get('erik_lab.last_cron_run')"   # seneste cron timestamp
ddev drush php:eval "\Drupal::queue('erik_lab_queue')->numberOfItems()"  # queue antal
```

---

## Tests

### PHPUnit — Functional (`tests/src/Functional/LabFormTest.php`)

Fuld HTTP-request-stak. Tester `LabForm`:

| Testmetode | Hvad den verificerer                              |
|---|---------------------------------------------------|
| `testFormRendersExpectedFields` | Rute 200, alle tre felter + submit knap til stede |
| `testShortGreetingFailsValidation` | "Hi" → valideringsfejl, ingen succesbesked        |
| `testValidSubmissionSavesConfig` | Gyldigt submit → Config API værdier gemt korrekt  |
| `testUnprivilegedUserIsDenied` | Bruger uden rettighed → 403                       |

```bash
ddev exec vendor/bin/phpunit modules/custom/erik_lab/tests/src/Functional
```

### PHPUnit — Kernel (`tests/src/Kernel/NodeListerTest.php`)

Entity API + database, ingen browser. Tester `NodeLister` servicen:

| Testmetode | Hvad den verificerer                       |
|---|--------------------------------------------|
| `testGetPublishedNodesOnlyReturnsPublished` | Kladde noder udelukkes                     |
| `testGetPublishedNodesRespectsLimit` | `$limit` argumentet overholdes             |
| `testLoadNodeReturnsCorrectEntity` | Returnerer korrekt entity for et kendt NID |
| `testLoadNodeReturnsNullForMissingNid` | Returnerer `NULL` for NID 99999            |

```bash
ddev exec vendor/bin/phpunit modules/custom/erik_lab/tests/src/Kernel
```

### Kør alle PHPUnit tests for dette erik_lab

```bash
ddev exec vendor/bin/phpunit --group erik_lab
```

---

### Behat — acceptancetests (`tests/features/erik_lab/`)

Feature-filer ligger i projektroden under `tests/features/erik_lab/`.
Custom step-definitioner: `tests/bootstrap/FeatureContext.php`.

| Feature-fil | Scenarier | Hvad den dækker |
|---|---|---|
| `lab_form.feature` | 3 | Formular renders, valideringsfejl ved kort input, vellykket gemning (admin-rolle) |
| `node_list.feature` | 3 | Side tilgængelig for auth/anon-brugere, publiceret node vises i listen |

Alle scenarier bruger `@api` — Drupal Extension opretter og rydder automatisk op i brugere og
noder efter hvert scenarie via Entity API. **Ingen hardkodede adgangskoder.**

#### Opsætning (første gang)

`behat.yml` er gitignoreret og må ikke committes — den indeholder lokale stier og URLs der
varierer per maskine. En template ligger klar:

```bash
cp behat.yml.dist behat.yml
# Ret base_url og drupal_root hvis nødvendigt (DDEV-defaults virker uden ændringer)
```

| Fil | Git | Formål |
|---|---|---|
| `behat.yml.dist` | ✅ Committed | Template med pladsholderværdier — commit denne |
| `behat.yml` | ❌ `.gitignore` | Lokal kopi med rigtige værdier — commit **aldrig** denne |

#### Hvor kører man Behat-tests?

| Miljø | Kør? | Bemærkning |
|---|---|---|
| **DDEV / lokal** | ✅ Ja | Standard udviklingsflow |
| **CI/CD** (GitHub Actions m.fl.) | ✅ Ja | Frisk Drupal-installation pr. PR — anbefalet |
| **Staging** | ⚠️ Muligt | Kun hvis miljøet nulstilles efter kørsel |
| **Produktion** | ❌ Aldrig | `api_driver: drupal` opretter og sletter rigtige noder/brugere i databasen |

Behat med `api_driver: drupal` bootstrapper Drupal direkte og skriver til databasen via Entity API.
På produktion vil det forurene live-data. Kør altid Behat på et isoleret miljø.

#### Kørsel

```bash
# Kør alle Behat-tests
ddev exec vendor/bin/behat --config behat.yml

# Kør én feature-fil
ddev exec vendor/bin/behat --config behat.yml tests/features/erik_lab/lab_form.feature

# Kør kun scenarier med et bestemt tag
ddev exec vendor/bin/behat --config behat.yml --tags @api

# List alle tilgængelige step-definitioner
ddev exec vendor/bin/behat --config behat.yml -dl

# Valider feature-filer uden at ramme sitet
ddev exec vendor/bin/behat --config behat.yml --dry-run
```

---

## Twig debug tilstand

I `sites/default/development.services.yml`:

```yaml
parameters:
  twig.config:
    debug: true
    auto_reload: true
    cache: false
```

---

## TODO Måske :)

- [ ] `hook_user_insert` → webhook
- [ ] JSON:API test: `GET /jsonapi/node/article`
- [ ] Custom block plugin (`src/Plugin/Block/`)
- [ ] Layout Builder Aktivér på en indholdstype
- [ ] Event subscriber (`src/EventSubscriber/`)
