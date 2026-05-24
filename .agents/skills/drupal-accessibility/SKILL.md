---
name: drupal-accessibility
description: Dispatcher for Drupal accessibility guidance. Use when generating, reviewing, or modifying Drupal code, theming, JavaScript, or test coverage with accessibility implications, or when drafting accessibility issues for the issue queue. Routes work to a specialised sub-skill based on file type or task — server-side Form API (drupal-a11y-fapi), DOM and CSS (drupal-a11y-dom), interaction and testing (drupal-a11y-dynamic), or QA and issue authoring (drupal-a11y-qa). Enforces Drupal Accessibility Coding Standards, WCAG 2.2 AA (including the new SC 2.5.8 24×24 px target size), Drupal-native hiding patterns over hand-rolled ARIA, and the move from Nightwatch to Playwright plus Axe-core inside PHPUnit.
metadata:
  status: draft
  drupal-version: "10.3+"
  last-reviewed: 2026-05
---

# Drupal accessibility: dispatcher

## When to use this guidance

You are working on code, markup, styles, JavaScript, or tests for a Drupal
project (core, contrib, or custom) where accessibility is in scope, or you are
drafting an accessibility-focused issue or merge request. Accessibility in
Drupal has Drupal-specific idioms that differ from generic web advice: the Form
API generates ARIA associations for you, the theme system has opinionated
hiding utilities, the Drupal.announce() API replaces hand-rolled `aria-live`
regions, and the testing story is mid-shift from Nightwatch to Playwright plus
Axe-core inside PHPUnit. Default web-accessibility advice from training data
will frequently steer agents away from these idioms. This dispatcher exists to
route the work to the sub-skill that knows the Drupal way to do it.

This skill does not contain the rules itself. It selects which sub-skill
applies and sets the cross-cutting expectations every sub-skill enforces.

## Routing logic

Pick the sub-skill that matches the **file type or task**. When more than one
applies (a feature usually touches Form API, theme, and JavaScript at once),
load each in turn for the slice of work it owns. Do not try to enforce one
sub-skill's rules from another.

| Trigger                                                                 | Load                                            |
|-------------------------------------------------------------------------|-------------------------------------------------|
| `*.module`, `*.inc`, `*Form.php`, `src/Form/`, render-array work        | `drupal-a11y-fapi/SKILL.md`                     |
| `*.css`, `*.pcss`, `*.html.twig`, theme libraries, layout work          | `drupal-a11y-dom/SKILL.md`                      |
| `*.js`, behaviours, AJAX, modals, `*Test.php` covering UI behaviour     | `drupal-a11y-dynamic/SKILL.md`                  |
| User asks for an accessibility "review", "audit", or "issue draft"      | `drupal-a11y-qa/SKILL.md`                       |

If the file extension is ambiguous (for example a Twig template that also
defines inline JavaScript), load both relevant sub-skills.

## Cross-cutting requirements (apply in every sub-skill)

These are not negotiable across the whole accessibility surface and are
restated, with examples, inside each sub-skill.

**Conformance baseline.** WCAG 2.2 AA. SC 2.5.8 *Target Size (Minimum)* is
new in 2.2 and frequently missed by AI-generated UI: every pointer/touch
target must be at least 24×24 CSS pixels unless one of the spec's exceptions
applies. Do not regress to 2.1 reasoning when the user has not asked you to.

**Semantic HTML first; no redundant ARIA.** Reach for native HTML5 elements
before any ARIA role, state, or property. Do not write `role="navigation"`
on a `<nav>`, `role="button"` on a `<button>`, `role="main"` on a `<main>`,
or `role="list"` on a `<ul>` — these are at best redundant and at worst
break the semantics the browser already provides. ARIA exists to fill gaps
in HTML, not to repeat it.

**Prefer Drupal idioms over hand-rolled ARIA.** Drupal's Form API,
`hide-content-properly` utilities, and `Drupal.announce()` already produce
correct, tested accessibility output. Hand-written `aria-*` attributes,
custom live regions, and ad-hoc visually-hidden CSS are almost always wrong
or duplicative. If a sub-skill points at a Drupal API, use it; do not invent
parallel ARIA on top. In particular, generate semantic markup through the
Render API (`#type`) rather than raw HTML strings in `#markup`.

**Testing uses Playwright and Axe-core in PHPUnit, not Nightwatch.**
Nightwatch is being removed from core (issue #3553673). New accessibility
tests must be written in Playwright at the browser level, and Axe-core
scans should run in PHPUnit at the render-array level (issue #3338664).
Existing Nightwatch tests should not be extended; convert them when you
touch them.

**AI disclosure on every issue and merge request.** When you generate an
accessibility patch, test, or issue draft, the resulting drupal.org artefact
must disclose AI assistance and map findings to specific WCAG success
criteria. The `drupal-a11y-qa` sub-skill owns the template; every other
sub-skill must defer to it for issue text.

## What not to do

Do not generate ARIA attributes when a Form API element type would produce
the same association automatically. Hand-rolled `aria-labelledby` and
`aria-describedby` on a Drupal form field is almost always either redundant
with Form API output or actively conflicting with it.

Do not introduce custom CSS for hiding content. Drupal ships `.visually-hidden`,
`.hidden`, `[hidden]`, and the `@media (scripting)` `.js-show` / `.js-hide`
pattern. Each of these has a specific meaning and the sub-skill covers them.

Do not extend Nightwatch coverage. If a feature needs new UI-level
accessibility tests, write them in Playwright per issue #3553673 and use the
PHPUnit Axe-core scan from issue #3338664 for render-array assertions.

Do not file an accessibility issue without WCAG SC mapping, the automated
tool used (Axe, Playwright, manual), and steps to reproduce. Issues without
this scaffolding are noise. The `drupal-a11y-qa` sub-skill enforces this.

Do not assume an accessibility win because Axe reports zero violations. Axe
catches roughly a third of WCAG issues; manual keyboard, screen-reader, and
zoom checks are still required for any non-trivial change.

## See also

- [Drupal Accessibility Coding Standards](https://www.drupal.org/docs/getting-started/accessibility/accessibility-coding-standards)
- [Hiding Content Properly](https://www.drupal.org/docs/getting-started/accessibility/hide-content-properly)
- [WCAG 2.2 SC 2.5.8 Target Size (Minimum)](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html)
- [Issue #3553673 — Adopt Playwright for browser-level testing](https://www.drupal.org/project/drupal/issues/3553673)
- [Issue #3338664 — Axe-core scans in PHPUnit](https://www.drupal.org/project/drupal/issues/3338664)
- [Issue #3587661 — Reference accessibility issue (reporting style)](https://www.drupal.org/project/drupal/issues/3587661)
- Sub-skills: `drupal-a11y-fapi/`, `drupal-a11y-dom/`, `drupal-a11y-dynamic/`, `drupal-a11y-qa/`
- Source material: Mike Gifford (Drupal Core Accessibility Maintainer)
