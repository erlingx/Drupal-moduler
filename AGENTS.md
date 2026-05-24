## Project-specific skills

The following skill files contain mandatory guidance for working in this project.
Read them at the start of every session before writing any code.
For the developer: install from: https://www.skills.sh/madsnorgaard/agent-resources/drupal-expert

- .agents/skills/drupal-expert/SKILL.md — Drupal coding standards, patterns, and best practices. ALWAYS read this before writing any Drupal PHP, YAML, or Twig.

---

# AI Agent Instructions — Drupal 10 Clean

This file contains instructions and known gotchas for AI coding assistants
working in this project.

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

### The fix

The &#10 bug affects BOTH create_file AND insert_edit_into_file when creating NEW
files.  insert_edit_into_file only produces correct newlines when the file already
exists on disk.

Rule:
- Edit existing file  → insert_edit_into_file (editor tool) ✅
                        writes in-place, same inode, PhpStorm auto-reloads

- Create NEW file     → insert_edit_into_file, then immediately run the Python fix below
                        Then: File > Reload from Disk in PhpStorm

NEVER use:
- create_file tool           — produces &#10 literal sequences, no workaround
- sed -i with semicolon      — entity is &#10 WITHOUT semicolon, sed won't match
- perl -i                    — same issue + may corrupt UTF-8/Danish characters

### Verified fix command (tested 2026-05-16)

The entity is stored as &#10 WITHOUT a trailing semicolon.
sed and perl do NOT reliably fix this. Use Python raw bytes:

    python3 -c "
    f = '/absolut/sti/til/fil.md'
    data = open(f, 'rb').read()
    open(f, 'wb').write(data.replace(b'&#10', b'\n'))
    print('Fixed:', f)
    "

Then reload the file in PhpStorm: File > Reload from Disk (or right-click > Reload from Disk).

This is safe for UTF-8 files including Danish æøå — raw bytes mode does not affect
multi-byte characters.

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

---



<!-- ai-best-practices:start -->
<!-- Do not edit by hand inside the ai-best-practices markers in AGENTS.md; this block is regenerated when you update drupal/ai_best_practices. -->

This file will contain a list of skills that are relevant to Drupal development and site building.

# Documentation
./vendor/drupal/ai_best_practices/skills/drupal-writing-documentation/SKILL.md
  When to use: You are writing, editing, or reviewing documentation for Drupal
  core, a contrib module, or drupal.org, including wiki pages, API docblocks,
  README files, and user guides.
  Covers: Where Drupal documentation lives and how to edit each type, writing
  good API documentation, Drupal content style guide conventions, common pitfalls
  (scope creep, non-Drupal content, over-documentation), and how to get help.

# Automated testing
./vendor/drupal/ai_best_practices/skills/drupal-automated-testing/SKILL.md
  When to use: You are writing, modifying, or choosing a type for automated tests
  in Drupal core, contrib, or custom modules.
  Covers: Test type selection (Functional, Kernel, Unit, FunctionalJavascript),
  common traps (dual-container, Kernel setup, waitForElement flakiness),
  PHPUnit conventions, and environment setup.

<!-- ai-best-practices:end -->
