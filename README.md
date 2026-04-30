# PG OSCE Moodle activity

`mod_pgosce` is a compact Moodle activity module for postgraduate OSCE / structured oral examination marking.

## Compatibility target

- Moodle 3.11 and later
- PHP 7.4 and later

## Main features

- Course activity named **PG OSCE**
- Teacher/admin editable rubric with sections, questions and answer-key criteria
- Optional student-facing station instructions from section names and rich-text section descriptions
- Blank station rubric: teachers create the questions, answer-key criteria and marks for each station
- Teacher assessment per student
- Multiple assessor attempts are averaged after finalization
- Gradebook synchronization using Moodle `grade_update()`
- Student report release toggle
- Excel-style CSV exports for averaged marker scores and individual marker scores
- Detailed CSV exports for criterion-level audit data
- Moodle backup/restore support, so course activity duplication copies the OSCE setup and rubric
- Moodle activity icons in `pix/icon.svg` and `pix/monologo.svg`
- Moodle course reset options for deleting assessment attempts/grades and optionally deleting station rubrics
- Rich-text rubric fields using Moodle's editor and plugin file areas for uploaded media

## Co-installation safety

This plugin is intentionally separate from `mod_verbalfeedback` and can be installed on the same Moodle site without sharing database tables, capabilities or component names.

- Component: `mod_pgosce`
- Folder: `mod/pgosce`
- Main table: `{pgosce}`
- Related tables: `{pgosce_section}`, `{pgosce_question}`, `{pgosce_criterion}`, `{pgosce_attempt}`, `{pgosce_score}`
- Capabilities: `mod/pgosce:*`
- Language pack: `lang/en/pgosce.php`
- PHP callbacks/functions: `pgosce_*`

No `verbalfeedback_*`, `mod_verbalfeedback`, or `mod/verbalfeedback:*` identifiers are used.

Default teacher permissions are declared in `db/access.php`. The plugin does not assign capabilities
or theme settings inside install hooks, because Moodle registers those during its own plugin flow.

The plugin declares Moodle's core module purpose as assessment. Theme-specific appearance settings
are left to the theme, avoiding cross-version theme constant issues.

## Installation

This repository is laid out with the Moodle plugin files at the repository root. For a Moodle site,
clone or copy this repository into:

```text
{moodle-dirroot}/mod/pgosce
```

For example:

```bash
git clone https://github.com/your-org/moodle-mod_pgosce.git /path/to/moodle/mod/pgosce
```

Then visit Moodle Site administration > Notifications, or run:

```bash
php admin/cli/upgrade.php
```

## Workflow

1. Add a **PG OSCE** activity to a course.
2. Open **Edit rubric** and create the station sections, candidate-facing section descriptions, questions, answer-key criteria and marks.
3. Assess each student from the activity page.
4. Use **Save and finalize** to push the calculated mark into the Moodle gradebook.
5. Export all criterion-level data as CSV for Excel analysis.

Teachers can turn **Show section instructions to students** on or off in the activity settings or from
the **Edit rubric** page. When enabled, students can see the section names and section descriptions
on the activity page. They cannot see question prompts, criteria, marks, assessor comments or marking
reports unless reports are released.

Use Moodle's normal activity **Duplicate** action to copy a PG OSCE station. The duplicate keeps the
activity settings, intro, rubric content and uploaded rubric media. Student assessment attempts are
only included when Moodle backup/restore is explicitly run with user data.

Rubrics can be edited after attempts exist. Existing criterion IDs are preserved when possible, so marks
remain attached to edited criteria. If criteria are removed, only scores for those removed criteria are
deleted and should be reviewed by assessors.
