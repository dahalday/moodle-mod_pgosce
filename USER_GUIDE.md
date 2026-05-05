# PG OSCE User Guide

This guide explains how to use the PG OSCE Moodle activity from two viewpoints:

1. Non-editing teachers who assess assigned stations.
2. Teachers, managers, admins and course creators who configure, manage and review the activity.

PG OSCE is designed for postgraduate OSCE, structured oral examination and station-based assessment workflows. It lets a course team create candidate-facing station instructions, examiner questions, answer-key criteria, marks, assessor comments, exports and gradebook-linked scores.

## 1. Instructions For Non-Editing Teachers

### Your Role In PG OSCE

As a non-editing teacher, you act as an assessor. You can mark students for PG OSCE stations that have been assigned to you, but you do not edit the station setup, import rubrics, export all marks or change activity settings.

This follows Moodle's usual non-editing teacher idea: you can view and grade student work, but you do not alter course activities or resources.

### What You Can See

You should only see PG OSCE stations that have been assigned to you.

Depending on how the activity is configured, student identity may be shown as:

- Moodle user ID only
- ID number only
- Moodle user ID and ID number

Student names may be hidden from non-editing teachers for privacy. If you cannot identify the correct student from the identifiers shown, ask the course teacher, manager or administrator before marking.

### Open An Assigned Station

1. Open the Moodle course.
2. Find the assigned PG OSCE activity.
3. Open the activity.
4. Select the student/candidate you are assessing.
5. Click **Assess**.

If an OSCE station is not visible, you may not be assigned to it. Contact the course teacher or administrator.

### Use The Assessment Screen

On the assessment screen, review the station, question and marking criteria. Depending on activity settings, marks may be entered using buttons or a text box.

Use the comment boxes for important observations, borderline decisions or feedback notes. Comments can be useful for later review, moderation and student report release.

### Use The Timer

If the station timer is enabled, a countdown appears during assessment. Reminder messages may appear, for example at 2 minutes and 1 minute remaining.

The reminder popup disappears automatically and does not stop the countdown. Continue assessing unless your local examination procedure says otherwise.

### Save Versus Save And Finalize

There are two important buttons:

- **Save** keeps your work as a draft or in-progress assessment.
- **Save and finalize** submits your official marker score.

Only finalized scores count toward average marker exports and Moodle gradebook calculations. Draft scores remain useful for review but do not affect the student's official average.

A finalized score of zero is treated as a true zero and counts normally.

### After Finalizing

After you finalize, the assessment may become locked for non-editing teachers. If you need to correct a finalized mark, contact an editing teacher, manager or administrator.

### Reports

If available, you may open a report for a student assessment. Student-facing reports are controlled separately by the course team. Students cannot see questions, criteria, marks or comments unless reports are released.

### Good Practice For Assessors

- Check the student identifier carefully before marking.
- Use **Save** if you are not finished.
- Use **Save and finalize** only when the assessment is ready to count.
- Add comments where a mark may need later explanation.
- Report wrong station assignments or missing students to the course team.

## 2. Instructions For Teachers, Managers, Admins And Course Creators

### Your Role In PG OSCE

Editing teachers, managers, admins and course creators manage the PG OSCE activity. You can create stations, edit rubrics, configure visibility, assign assessors, import/export PG OSCE GIFT, review reports, export marks and manage gradebook behavior.

### Role Summary

| Role | Typical PG OSCE access |
| --- | --- |
| Student | May see station instructions, released reports and/or gradebook grades depending on settings |
| Non-editing teacher | Can assess assigned PG OSCE stations only; no rubric editing or mark export by default |
| Teacher | Full course-level setup, rubric editing, marking, export and assessor assignment |
| Manager/Admin | Full access, including site/course oversight |
| Course creator | May configure activities where Moodle role permissions allow |

### Installation Or Upgrade

Install PG OSCE as a Moodle activity module.

1. Use a plugin ZIP containing one top-level folder named `mod_pgosce`.
2. In Moodle, go to **Site administration > Plugins > Install plugins**.
3. If Moodle asks for plugin type, choose **Activity module (mod)**.
4. Continue through validation and upgrade screens.
5. After installation, add PG OSCE as a course activity.

Before upgrading a live site, back up the Moodle site and database. For testing, install the fork ZIP on a staging Moodle site first.

### Create A PG OSCE Activity

1. Open the course.
2. Turn editing on.
3. Add an activity or resource.
4. Choose **PG OSCE**.
5. Enter the activity name.
6. Configure visibility, timer, grade and common Moodle settings.
7. Save and display.

### Configure Visibility Controls

PG OSCE separates student visibility into three layers.

| Setting | Effect |
| --- | --- |
| Show section instructions to students | Students can see the PG OSCE item and candidate-facing section names/descriptions. Questions, criteria, marks and comments stay hidden. |
| Release student reports | Students can see their released report, including assessed content allowed by the report view. |
| Show grades in gradebook | Finalized grades are visible in Moodle gradebook. This does not release station questions, criteria or comments by itself. |

If **Show grades in gradebook** is enabled and Moodle requires the activity item to exist for gradebook display, students may see a minimal PG OSCE course-page item. The detailed station content remains hidden unless **Show section instructions to students** or **Release student reports** is enabled.

### Configure Assessor Privacy

Use **Identifier shown to non-editing teachers** to choose what assigned non-editing teachers see instead of student names:

- Moodle user ID and ID number
- Moodle user ID only
- ID number only

Editing teachers, managers, admins and course creators continue to see student names plus Moodle user ID and ID number when present.

### Assign Non-Editing Teachers

Use **Assign assessors** from the PG OSCE activity page.

You can assign non-editing teachers:

- As course-default PG OSCE assessors, giving access to all PG OSCE stations in the course.
- To one specific PG OSCE station only.

Non-editing teachers should not see unassigned PG OSCE stations. If they can see too much, review role permissions, PG OSCE assignment settings and course visibility.

### Edit The Rubric Manually

Open **Edit rubric** from the activity page.

A rubric is made of:

- Sections
- Section descriptions/candidate instructions
- Questions
- Question prompts
- Marking criteria
- Max marks

Section descriptions, question prompts and criteria use Moodle's editor, so images and media can be added where Moodle editor/file settings allow.

### Mark Entry Display

The activity has general mark entry settings, and individual criteria can override them.

Controls may include:

- Global setting
- Buttons
- Text box

Button scales may include:

- Whole marks, for example 0, 1, 2
- Half marks, for example 0, 0.5, 1
- Quarter marks, for example 0, 0.25, 0.5

Use buttons for quick standardized marking. Use text boxes where a criterion needs flexible numeric entry.

### Edit Rubrics After Attempts

Rubrics can be edited after attempts exist, but do this carefully.

Recommended process:

1. Export marks before a major rubric change.
2. Review whether existing criteria should be edited or removed.
3. Preserve existing criteria where possible so marks remain attached.
4. Ask assessors to review affected students after significant rubric edits.

If a criterion is removed, marks attached to that removed criterion may be deleted.

### Use PG OSCE GIFT Import/Export

PG OSCE GIFT is a plain-text rubric format inspired by Moodle quiz GIFT. It is not the same as Moodle quiz GIFT, because OSCE stations need sections, candidate instructions, examiner prompts, criteria and marks.

Use **PG OSCE GIFT import/export** to:

- Download the current rubric as reusable text.
- Paste or upload a `.gift` or `.txt` rubric.
- Prepare rubrics from Word documents using AI-assisted conversion.

Importing PG OSCE GIFT replaces the current rubric. If attempts already exist, export marks first and review the station after import.

Basic PG OSCE GIFT structure:

```text
# PG OSCE GIFT
::Station:: Example OSCE station

[Settings]
ShowStudentInstructions: no
ReleaseStudentReports: no
ShowGradesInGradebook: yes
AssessorIdentifierDisplay: both

[Section] Candidate instructions
Candidate-facing instructions go here.

[Question] Examiner question title
Prompt: Examiner prompt goes here.
= First markable criterion ::1
= Second markable criterion ::0.5
```

### Convert A Word OSCE To PG OSCE GIFT With AI

Use this prompt with copied Word content:

```text
Convert the OSCE station below into PG OSCE GIFT format for a Moodle PG OSCE plugin.

Context:
- The source may be copied from a Word document and may contain OSCE/SCOE tables.
- In tables, candidate instructions, examiner questions, prompts, answer keys, criteria and marks may be in separate cells.
- Sometimes several markable criteria are grouped together inside one table cell.

Rules:
- Output only plain text PG OSCE GIFT.
- Use ::Station:: followed by a concise station name.
- Include a [Settings] block with:
  ShowStudentInstructions: no
  ReleaseStudentReports: no
  ShowGradesInGradebook: yes
  AssessorIdentifierDisplay: both
- Put candidate-facing scenario/instructions under [Section].
- If the Word document has multiple stations or domains, create one [Section] per station/domain.
- Put each examiner question under [Question].
- Put the examiner question text after Prompt:.
- Convert the answer key or marking guide into criteria lines starting with =.
- End every criterion with ::mark, for example ::1, ::2, ::0.5.
- Preserve clinical meaning but make criteria concise and markable.
- If one table cell contains several separate criteria, split them into separate = lines.
- Split grouped criteria when they are separated by line breaks, semicolons, numbering, bullets, commas with separate actions, or phrases such as "and", "including", "mentions", "lists", "discusses".
- Do not split a cell if it is clearly one holistic criterion with one mark.
- If a grouped cell has a total mark but no individual marks, divide marks logically across the criteria when obvious; otherwise use ::1 for each criterion and keep the total reasonable.
- If the source gives individual marks, preserve them exactly.
- Do not invent clinical facts that are not present in the document.
- Do not include student instructions as marking criteria unless they are also in the answer key.
- Do not include markdown tables.
- Do not include explanations outside the PG OSCE GIFT text.

OSCE station text:
[paste the Word document content here]
```

### Marking And Finalization Logic

Multiple markers can assess the same student.

- **Save** creates or updates a draft assessment.
- **Save and finalize** submits that marker's official score.
- Average exports use finalized marker scores only.
- Moodle gradebook calculations use finalized marker scores only.
- Individual exports still show draft/in-progress rows for audit.
- A finalized zero is treated as a real zero and counts normally.

This prevents testing or unfinished assessor drafts from lowering the official average.

### Export Marks

Use **Export all marks** from the activity page.

Export types:

- **Excel-style CSV: average score across markers**
- **Excel-style CSV: individual marker scores**
- **Detailed CSV: average criterion scores**
- **Detailed CSV: individual marker criterion scores**

Average exports combine finalized marker scores only. Individual exports keep each marker row visible, including drafts, for audit and troubleshooting.

### Gradebook

PG OSCE uses Moodle gradebook integration. Finalized scores are converted to the configured activity grade and pushed to the Moodle gradebook.

Use **Show grades in gradebook** if students should see the mark in their gradebook. This is separate from releasing the detailed PG OSCE report.

### Timer Settings

The station timer is configured in activity settings.

Set:

- Timer duration in minutes
- First reminder, for example 2 minutes remaining
- Second reminder, for example 1 minute remaining

Set the duration to `0` to disable the timer.

### Backup, Restore, Duplicate And Reset

Use Moodle's normal activity duplicate, backup and restore tools to reuse stations.

- **Duplicate** copies the PG OSCE setup inside the same course.
- **Activity backup** can create an `.mbz` backup of the activity.
- **Activity restore** can restore a backed-up activity into a course.
- **Course reset** can delete assessment attempts and grades.
- Optional PG OSCE reset can also delete station rubrics/questions when a fully blank activity is needed.

Before resetting or replacing rubrics, export any marks you may need later.

### Troubleshooting

| Problem | What to check |
| --- | --- |
| Non-editing teacher cannot see station | Confirm they are assigned to the PG OSCE or as a course-default assessor |
| Non-editing teacher sees wrong identifier | Check **Identifier shown to non-editing teachers** |
| Student cannot see station instructions | Check **Show section instructions to students** |
| Student cannot see detailed report | Check **Release student reports** |
| Student cannot see gradebook grade | Check **Show grades in gradebook** and Moodle gradebook visibility |
| Average export seems too low | Confirm the intended markers used **Save and finalize** |
| Draft marks appear in individual export | This is expected for audit; drafts do not affect averages |
| PG OSCE GIFT import fails | Check that the file has at least one section, one question and one criterion with marks |
| Rubric changes affected marks | Review removed criteria and ask assessors to re-check affected students |

### References

- Moodle Non-editing teacher role: https://docs.moodle.org/en/Non-editing_teacher_role
- Moodle GIFT format: https://docs.moodle.org/401/en/GIFT_format
- Moodle Activity backup: https://docs.moodle.org/en/Activity_backup
- Moodle Activity restore: https://docs.moodle.org/en/Activity_restore
