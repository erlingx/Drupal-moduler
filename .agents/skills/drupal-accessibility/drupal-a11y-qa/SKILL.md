---
name: drupal-a11y-qa
description: Issue-authoring and review rules for Drupal accessibility work. Use when drafting an accessibility issue, merge-request description, or change record, or when reviewing one. Enforces the structured field block (Bug ID instance/pattern, URL, simplified and full-DOM XPath, WCAG SC with level, tool rule ID, severity, frequency, screen type, colour mode), the HTML snippet, AI disclosure, one-issue-per-criterion scoping, and the manual-checks-required-before-RTBC rule. Pair with REFERENCE_BUG_REPORT.md in this directory for the fillable template. Aligned with the upstream ACCESSIBILITY_BUG_REPORTING_BEST_PRACTICES guide. Loaded by the drupal-accessibility dispatcher.
metadata:
  status: draft
  drupal-version: "10.3+"
  last-reviewed: 2026-05
---

# Drupal accessibility: QA and issue authoring

## When to use this guidance

You are about to file an accessibility issue on drupal.org, write a
merge-request description for an accessibility change, draft a change
record, or triage someone else's accessibility report. This sub-skill
is the contract every such artefact must meet before it leaves the
agent. The companion file `REFERENCE_BUG_REPORT.md` is the fillable
template; this SKILL.md explains the rules behind it.

The fields below are non-negotiable. The format aligns with the
upstream [Accessibility Bug Reporting Best Practices](https://mgifford.github.io/ACCESSIBILITY.md/examples/ACCESSIBILITY_BUG_REPORTING_BEST_PRACTICES.html)
guide so reports are interoperable across projects, with Drupal-specific
additions for theme, AT pair, and sub-skill citation.

## Structured field block (required at the top of every issue)

Every report opens with the block in `REFERENCE_BUG_REPORT.md`. Fields
are required unless explicitly marked optional.

**Bug ID — two levels.** Every violation gets a stable identifier so
pipelines can deduplicate, track regressions, and group recurring
patterns across pages.

| Level         | Inputs to hash                                                  | Purpose                                                                                                                       |
|---------------|-----------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------|
| `instance_id` | page path + CSS selector + rule ID + screen type                 | Uniquely identifies one occurrence on one specific page. Same element, same rule, same page, same viewport class = same ID.   |
| `pattern_id`  | CSS selector + rule ID + screen type                             | Identifies the recurring template-level pattern across pages. Multiple pages sharing the same component share the pattern_id. |

Format: `[PREFIX]-[8-char hex]`. Use `DRP-` for Drupal core,
`DRPC-<short>` for contrib (e.g. `DRPC-WEBFORM-`), and a project-defined
prefix for client work. The hex is the first 8 characters of a SHA-256
of the inputs joined with `|`. Hash inputs use the **CSS selector** from
`axe-core` `node.target`, not the XPath, because the CSS selector is
what the scanner emits natively and is stable across XPath generation
changes. Include both the simplified XPath and the full DOM-path XPath
in the report regardless.

**URL.** Full URL including query string and fragment. If the same
violation appears on multiple URLs, list them or describe the pattern
("all `/node/*/edit` pages").

**XPath (simplified) and Full DOM path.** Both forms. Simplified XPath
is human-readable, the full path is for deterministic replay when IDs
change dynamically.

**WCAG SC.** Number, name, and level. Format: *"WCAG 2.2 SC 2.5.8 Target
Size (Minimum) — Level AA"* with a link to the W3C Understanding
document. One issue, one criterion (see below).

**Rule.** Tool name and version (e.g. `@axe-core/playwright 4.10`,
Pa11y, manual NVDA pass) and the rule ID it flagged (`target-size`,
`button-name`). When multiple tools flag the same issue, list each.

**Severity.** Use the upstream taxonomy consistently:

| Level     | Definition                                                            |
|-----------|-----------------------------------------------------------------------|
| Critical  | Users cannot complete a core task at all.                             |
| High      | Significant barrier that degrades or blocks a key workflow.           |
| Medium    | Noticeable barrier with a workaround available.                       |
| Low       | Minor issue with minimal real-world impact.                           |

Frequency amplifies severity: a Low issue on every page or on a
high-traffic flow is treated as Medium. Document the adjustment in the
issue when applied.

**Frequency.** *"N instances on this page; M of P pages affected"*. For
automated scans, include aggregate counts. Deduplicate before filing —
do not file 200 identical `image-alt` issues; file one with the count.

**Screen type and colour mode.** `desktop` / `mobile` (inferred from
viewport width — < 768 px = mobile, ≥ 768 px = desktop) and
`light` / `dark`. Both are part of the hash inputs above.

**HTML snippet.** Minimal failing fragment. Capture at scan time so
the snippet survives later DOM changes.

## One issue, one criterion

A pull request that fixes three SCs across two components is three
issues. AI tools tend to bundle because the diff comes out as one patch.
Resist it. Reviewers triage by SC, the release notes group by SC, and
the fix history needs to be traceable per-criterion. Link the three
issues with *Related issues* and a brief note in each that this fix
coordinates with the others.

## Steps to reproduce, environment, impact

Numbered steps reproducible on a clean Drupal install. The Testing
Environment table in the template is a superset of the upstream guide:
add **Drupal core version** and **Theme** to browser, OS, AT, viewport,
zoom, forced-colours, and `prefers-reduced-motion`. The AT/browser pair
is required when the issue depends on assistive technology — keyboard-
only and JAWS-on-Edge findings can have different fixes.

Impact must name the disability groups affected ("keyboard-only users",
"NVDA users", "low-vision users at 200% zoom"). Generic "users with
disabilities" is not enough.

## Suggested fix cites a sub-skill

Every Suggested Fix names the sub-skill whose rule applies:
`drupal-a11y-fapi` (server-side render arrays), `drupal-a11y-dom`
(templates and styles), or `drupal-a11y-dynamic` (JavaScript and tests).
Cite the specific rule in the prose ("per drupal-a11y-dom, expand the
hit area to 24×24 with padding") so reviewers can verify the fix
follows project conventions without re-reading the whole sub-skill.

## Manual checks before RTBC

The Remaining Tasks block in the template is a checklist. Tests pass
*and* manual checks pass before RTBC. Axe alone catches roughly a third
of WCAG issues; an automated green is not a fix. The mandatory manual
checks are: keyboard-only walk, screen-reader pass with the relevant
AT, 200% zoom (and 400% for SC 1.4.10), and forced-colours when the
change is visual.

## AI disclosure: exact wording

Use this block verbatim in any issue, MR, or change record where AI
contributed:

```
**AI disclosure**

This contribution was prepared with assistance from an AI coding tool.
- Tool: <tool name and version, e.g. Claude Code>
- Used for: <specific tasks, e.g. drafting the patch, generating tests, drafting this issue>
- Reviewed by: <human reviewer's drupal.org username>
- Skills loaded: drupal-accessibility (sub-skills: <comma-separated list>)
```

The reviewed-by line is required — an unreviewed AI contribution does
not qualify for the project. *Skills loaded* is the dispatcher and the
sub-skills that informed the work; this lets reviewers spot when a
relevant sub-skill was missed.

## What not to do

Do not file an issue without the structured field block. Free-form
prose without Bug ID, URL, XPath, WCAG SC, Rule, Severity, Frequency,
Screen type, and Colour mode is rejected.

Do not omit the Bug ID. Pipelines need stable identifiers; without
them, the same regression is filed twice and fixes cannot be tracked.

Do not bundle multiple SCs in a single issue. File one per criterion
and link them.

Do not paste raw Axe JSON without the rule names and the affected
selectors highlighted. Reviewers should be able to find the failing
element from the issue text without re-running the tool.

Do not omit the AT/browser pair. A keyboard-only finding and a
JAWS-on-Edge finding can have different fixes.

Do not generate the issue text with AI and post it without review.
Disclosure is required even with review; posting without review is
not acceptable.

Do not rely on Axe alone. Pair the automated finding with at least
one manual check and document both.

Do not assume a passing automated scan means the issue is resolved.
Manual keyboard, screen-reader, and zoom checks are required before
RTBC.

## See also

- [Accessibility Bug Reporting Best Practices (upstream)](https://mgifford.github.io/ACCESSIBILITY.md/examples/ACCESSIBILITY_BUG_REPORTING_BEST_PRACTICES.html)
- [Creating or updating an issue report (Drupal contributor guide)](https://www.drupal.org/community/contributor-guide/reference-information/quick-info/creating-or-updating-an-issue-report)
- [Issue #3587661 — reference reporting style](https://www.drupal.org/project/drupal/issues/3587661)
- [WCAG 2.2 quick reference](https://www.w3.org/WAI/WCAG22/quickref/)
- Template: `REFERENCE_BUG_REPORT.md` in this directory.
- Dispatcher: `../SKILL.md`
- Sibling sub-skills: `../drupal-a11y-fapi/`, `../drupal-a11y-dom/`, `../drupal-a11y-dynamic/`
- Source material: Mike Gifford (Drupal Core Accessibility Maintainer)
