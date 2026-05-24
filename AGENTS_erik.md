# AI Agent Instructions — Drupal 10 Clean

This file contains instructions and known gotchas for AI coding assistants
working in this project.

---

## Writing Behat Tests

Available step definitions are listed in `STEPS.md`.
Read this file before writing or modifying Behat tests.
Use only step patterns from this file. Do not invent steps.

If `STEPS.md` does not exist or is outdated, regenerate it:

    vendor/bin/behat -di > STEPS.md

Regenerate after adding new Context classes or updating dependencies.

For detailed step documentation, see: vendor/drupal/drupal-extension/STEPS.md

---


## WARNING: File Creation — Newline Bug

### The problem
The create_file tool (and insert_edit_into_file in some cases) produces
files where newlines are stored as the literal two-character sequence
ampersand-hash-10-semicolon instead of real newline characters.
The file looks correct in the tool response, but on disk it is one long line.
This breaks:
- YAML parsing (Drupal silently ignores the module/theme)
- PHP syntax (fatal errors)
- Twig templates (rendered as raw text)
- .info.yml discovery (drush en fails with missing module/theme)

### How to detect it

    ddev exec cat themes/olivero_sub/olivero_sub.info.yml
    # Bad:  name: Olivero Sub[ampersand10]type: theme[ampersand10]...
    # Good: name: Olivero Sub
    #       type: theme

### The fix — always write new files via terminal heredoc

Use run_in_terminal with a bash heredoc. Single-quote the delimiter to
prevent shell variable expansion. CRITICAL: pick a delimiter that does NOT
appear as a standalone line in the file body.

    cat > /path/to/file.yml << HEOF
    name: My Module
    type: module
    core_version_requirement: ^10
    HEOF

Good delimiters: HEOF, TWIG_END, PHPEOF, AGENTS_END, YAMLDONE
Bad delimiters:  EOF (appears in code examples), END (appears in comments)

### PhpStorm inode rule — editing existing files

Use insert_edit_into_file (the editor tool) for ALL edits to existing files.
It writes in-place (same inode) so PhpStorm detects the change and prompts reload.

NEVER use sed -i or python write_text() on existing files — both swap the inode,
PhpStorm loses track and never shows the reload prompt.

Summary:
- New file       → cat > file << HEOF  (heredoc, terminal)
- Edit existing  → insert_edit_into_file (editor tool)

---

## Project overview

| Item              | Value                                              |
|-------------------|----------------------------------------------------|
| Drupal version    | 10                                                 |
| Local environment | ddev (ddev exec, ddev drush)                       |
| Docroot           | project root (docroot empty in .ddev/config.yaml)  |
| Default theme     | olivero_sub (subtheme of Olivero)                  |
| Experiment module | modules/custom/erik_lab                            |
| PHP in container  | 8.x  — check: ddev exec php -v                     |

---

## Common ddev/drush commands

    ddev drush cr                                          # cache rebuild
    ddev drush en <module> -y                              # enable module
    ddev drush theme:enable <theme> -y                     # enable theme
    ddev drush config:set system.theme default <theme> -y  # set default theme
    ddev drush cex / cim                                   # config export/import
    ddev drush php:eval "<code>"                           # run inline PHP
    ddev exec cat <file>                                   # read file in container
    ddev exec php -v                                       # PHP version in container
    ddev drush queue:list                                  # list queues
    ddev drush queue:run <queue>                           # run a queue
    ddev drush entity:delete node 1,2,3                    # delete entities
    ddev drush sql:query "SELECT * FROM users_field_data"  # raw SQL

---

## Notes on this project

- Pure learning/experiment sandbox — nothing goes to production.
- Owner: Erik Lautrup-Larsen (re-sharpening Drupal skills for job search).
- Roadmap: dokumenter/job/drupal-resharpen-roadmap.md
