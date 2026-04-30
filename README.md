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
- PG OSCE GIFT-style rubric import/export for preparing stations from Word documents
- Assessor assignment for non-editing teachers by course default or by individual station
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

Place the `pgosce` folder inside:

```text
{moodle-dirroot}/mod/pgosce
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
6. Use **PG OSCE GIFT import/export** to reuse or prepare rubrics as plain text.

Non-editing teachers can assess only when they are assigned as a course default OSCE assessor or
assigned to the individual station. They do not receive rubric editing, station import/export or marks
download permissions by default. Editing teachers, managers and admins keep full access.

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

## PG OSCE GIFT format

PG OSCE GIFT is a plain-text format inspired by Moodle GIFT, but designed for OSCE station rubrics.
It is not the same as Moodle quiz GIFT because OSCE stations need sections, candidate instructions,
examiner questions, answer-key criteria and marks.

Use **PG OSCE GIFT import/export** from the activity page to paste text, upload a `.gift` or `.txt`
file, or download the current station rubric.

Basic format:

```text
# PG OSCE GIFT
::Station:: GTN OSCE

[Settings]
ShowStudentInstructions: yes
ReleaseStudentReports: no

[Section] GTN
Candidate's instructions:
A 25-year-old Para 0 at gestational age 12 weeks presented with PV spotting.
A subsequent transvaginal ultrasound reveals findings suggestive of molar pregnancy.

[Question] Diagnosis and immediate management
Prompt: What is the likely diagnosis and what immediate management is needed?
= Recognises possible gestational trophoblastic disease ::2
= Mentions ultrasound and histology confirmation ::1
= Advises baseline and serial beta-hCG follow-up ::2

[Question] Follow-up counselling
Prompt: Explain follow-up and treatment indications after hydatidiform mole.
= Explains serial beta-hCG surveillance until normal and continued monitoring ::2
= Advises contraception during surveillance ::1
= Lists plateau, rise or persistence of hCG as treatment indications ::2
```

Formatting rules:

- `::Station:: Station name` is optional and is used as a label in the text file.
- `[Section] Section name` creates a rubric section. Text below it becomes the student-facing section description.
- `[Question] Question title` creates an examiner question inside the current section.
- `Prompt:` starts the examiner prompt for that question.
- Marking criteria start with `=`, `-`, or `*` and end with `::mark`.
- Example criterion: `= Gives contraception advice during hCG surveillance ::2`
- Importing replaces the current rubric. If attempts already exist, review existing marks afterwards.

## Making PG OSCE GIFT From Word

1. Open the OSCE Word document.
2. Identify the candidate scenario/instructions. Put that under `[Section]`.
3. Identify each examiner question. Put each one under `[Question]`.
4. Convert the answer key or marking scheme into one criterion per line.
5. Add marks at the end of each criterion using `::1`, `::2`, `::0.5`, etc.
6. Save as a plain `.txt` or `.gift` file, or paste directly into **PG OSCE GIFT import/export**.

AI prompt for converting a Word OSCE station:

```text
Convert the OSCE station below into PG OSCE GIFT format for a Moodle PG OSCE plugin.

Rules:
- Output only plain text PG OSCE GIFT.
- Use ::Station:: followed by a concise station name.
- Put candidate-facing scenario/instructions under [Section].
- Put examiner questions under [Question].
- Put examiner prompts after Prompt:.
- Convert the marking guide into criteria lines starting with =.
- End every criterion with ::mark.
- Preserve clinical meaning but make criteria concise and markable.
- Do not include markdown tables.
- Do not include explanations outside the PG OSCE GIFT text.

OSCE station text:
[paste the Word document content here]
```
