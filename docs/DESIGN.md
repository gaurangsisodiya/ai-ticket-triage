# Design: "civic records office"

The app is a support desk for software used by local government staff: clerks, council
staff, communications teams. The interface borrows from the place they work, a well-run
municipal clerk's office (paper, ink, ledgers, case files, official stamps), and stays a
modern, quiet, usable tool. All tokens live in `resources/css/app.css`; fonts are loaded in
`vite.config.js`.

## Typefaces

| Role | Family | Why |
|---|---|---|
| Interface text | **Public Sans** (400–700) | The typeface of the US Web Design System, made for government services. Plain, highly legible, familiar to the users. |
| Page titles, section titles, report figures | **Source Serif 4** (400, 600) | A sturdy text serif with optical sizes: gives titles and big numbers the authority of a printed report without looking decorative. |
| Case numbers, timestamps, model names, prompts, logs | **IBM Plex Mono** (400, 500) | Reads as "typed record" and keeps digits aligned. |

The layout must call `@fonts` before `@vite` in `<head>`: that directive writes the font CSS and
preload tags. Without it the families are built but never requested, and the browser silently
falls back to system fonts.

## Color

Warm paper and ink instead of cool greys, one civic navy for action, and muted official
status colors. Every text pair meets WCAG AA (4.5:1); ratios are against `paper`.

| Token | Hex | Use | Contrast |
|---|---|---|---|
| `paper` | `#F5F1E8` | page background | — |
| `surface` | `#FBF9F4` | forms, panels, table bodies | — |
| `sunk` | `#EDE7DA` | table headers, log blocks, row hover | — |
| `rule` | `#DDD5C6` | hairline rules (decorative) | — |
| `rule-strong` | `#9C917E` | rule under table headers and mastheads | — |
| `field` | `#8A7F6C` | form field borders | 3.5:1 (WCAG 1.4.11) |
| `ink` | `#1F1B16` | body text | 15.2:1 |
| `ink-2` | `#4D463C` | secondary text | 8.3:1 |
| `ink-3` | `#6B6357` | meta text, captions | 5.3:1 (4.8:1 on `sunk`) |
| `navy` | `#1E3A5F` | links, primary buttons, focus, active nav | 10.2:1; white on navy 11.5:1 |
| `ok` / `ok-tint` | `#2E6A3E` / `#E3ECDF` | resolved, auto-resolved, match | 5.7:1 / 5.3:1 on tint |
| `review` / `review-tint` | `#865A08` / `#F4E9CF` | needs review, drafts (ochre) | 5.4:1 / 5.0:1 on tint |
| `seal` / `seal-tint` | `#9E2A22` / `#F4E1DC` | high urgency, errors, misses | 6.6:1 / 5.9:1 on tint |
| `slate` / `slate-tint` | `#4A5766` / `#E6E9EC` | new, analyzing | 6.6:1 / 6.1:1 on tint |

Rule: color is never the only signal. Statuses always carry a text label, and urgency
carries a label plus a bar glyph.

## Type scale

Base 15px, roughly a 1.2 ratio, with larger steps for serif figures.

| Token | Size / line | Used for |
|---|---|---|
| `text-xs` | 12 / 16 | eyebrow labels, captions, mono meta |
| `text-sm` | 13 / 20 | helper text, table meta |
| `text-base` | 15 / 24 | body, table cells, form input |
| `text-lg` | 18 / 26 | section titles (serif) |
| `text-xl` | 22 / 30 | ticket subject |
| `text-2xl` | 28 / 36 | page titles (serif) |
| `text-3xl` | 36 / 40 | secondary figures |
| `text-4xl` | 48 / 48 | report numerals (serif) |

## Space, shape, motion

- 4px base grid (Tailwind's). Page gutter 32px on desktop, 16px on phones; 40px between
  page sections; table rows at least 52px tall.
- Corners 2–4px: paper is nearly square. Borders, never drop shadows.
- Transitions 120ms, color/opacity only, no movement. `prefers-reduced-motion` turns them off.
- Focus: a 2px navy outline with a 2px gap on every focusable element.

## Signature details (used sparingly)

| Detail | Where | Built with |
|---|---|---|
| Case numbers (`CASE-0042`) in mono | everywhere a ticket is referenced | `ref` |
| Rubber-stamp status: outlined, double border, uppercase, rotated 3° | ticket detail page only, once | `stamp` |
| Daily desk report: masthead with date and "Support Desk · Daily report", serif numerals between hairline rules, no boxed cards | overview | `numeral`, `eyebrow` |
| Ledger rows: label, dotted leader, count, thin bar underneath | bar lists | `leader` |
| Ledger tables: strong rule under the header, hairlines between rows | all tables | `ledger-head`, `th`, `td` |
| Internal memo for AI replies: From / To / Re / Source header, Sent or Draft label | ticket detail | `tag` |
| "Audit trail" of LLM calls as typed records | ticket detail | `typed` |
| Office-directory sidebar: "Support Desk / Office of Digital Services", text links, left-rule active marker | layout | — |
| Intake form with numbered fields and a "what happens next" note | new ticket | `field`, `label` |

Not used anywhere: gradients, glass effects, heavy shadows, emoji, icon-in-colored-circle
badges, decorative "AI" sparkles.
