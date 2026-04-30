# PG OSCE Fork Feature Plan

This fork is intended for the next feature set before merging back into the main PG OSCE plugin.

## Requested Changes

- Non-editing teachers should assess only. They should not edit/import/export stations or download marks.
- Editing teachers, managers and admins should keep full access.
- Assign non-editing teachers to all PG OSCE stations by default in a course, or to specific stations.
- Add configurable station timer settings.
- Modernize the assessor view using a Moodle-safe accordion/tap-to-score interface.

## Proposed Permission Split

- `mod/pgosce:assess`: enter marks for assigned stations.
- `mod/pgosce:manage`: edit station rubric.
- `mod/pgosce:assignassessors`: assign non-editing teacher assessors.
- `mod/pgosce:import`: import PG OSCE GIFT/station definitions.
- `mod/pgosce:export`: export marks and rubric data.
- `mod/pgosce:settimer`: configure station timer.

Non-editing teachers should normally receive only `view` and `assess`.

## UI Direction

Use Moodle-compatible Bootstrap/cards/collapse patterns:

- Sticky station summary with candidate, station, timer and live score.
- Candidate instruction banner.
- Accordion question panels.
- Tap-to-score buttons generated from criterion max marks.
- Inline criterion comments.
- Save draft and save/finalize actions.

Avoid theme-specific APIs, external fonts, and large custom full-page styling.

