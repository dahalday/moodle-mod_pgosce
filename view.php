<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Main PG OSCE activity page.
 *
 * @package    mod_pgosce
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/pgosce/lib.php');

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'pgosce');
$pgosce = $DB->get_record('pgosce', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pgosce:view', $context);
if (!pgosce_can_view_activity($pgosce, $context)) {
    throw new moodle_exception('activitynotavailable', 'pgosce');
}

$PAGE->set_url('/mod/pgosce/view.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(format_string($pgosce->name));
$PAGE->set_heading($course->fullname);
$PAGE->requires->css(new moodle_url('/mod/pgosce/styles.css'));

echo $OUTPUT->header();

$canassess = pgosce_can_assess_station($pgosce, $context);
$canmanage = has_capability('mod/pgosce:manage', $context);
$canassign = has_capability('mod/pgosce:assignassessors', $context);
$canimport = has_capability('mod/pgosce:import', $context);
$canexport = has_capability('mod/pgosce:export', $context);
$hasfullaccess = pgosce_has_full_access($context);
$hasrubric = pgosce_get_total_maxmark($pgosce->id) > 0;
$rubric = pgosce_get_rubric($pgosce->id);
$showinstructions = $rubric && ($canassess || $canmanage || !empty($pgosce->showstudentinstructions));

echo html_writer::start_div('pgosce-shell');
echo html_writer::start_div('pgosce-hero');
echo html_writer::tag('h2', format_string($pgosce->name));
echo html_writer::start_div('pgosce-hero-meta');
echo html_writer::tag('span',
    html_writer::span(get_string('grade', 'pgosce'), 'pgosce-stat-label') .
    html_writer::span(format_float($pgosce->grade, 2), 'pgosce-stat-value'),
    ['class' => 'pgosce-stat']);
echo html_writer::tag('span',
    html_writer::span(get_string('status', 'pgosce'), 'pgosce-stat-label') .
    html_writer::span($hasrubric ? get_string('rubric', 'pgosce') : get_string('createrubricfirst', 'pgosce'),
        'pgosce-stat-value'),
    ['class' => 'pgosce-stat']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::div(format_module_intro('pgosce', $pgosce, $cm->id), 'pgosce-panel pgosce-panel-muted');

if ($showinstructions) {
    echo html_writer::start_div('pgosce-panel');
    echo $OUTPUT->heading(get_string('candidateinstructions', 'pgosce'), 3);
    echo html_writer::start_div('pgosce-candidate-instructions');
    $sectionnumber = 1;
    foreach ($rubric as $section) {
        if (empty($section['name']) && empty($section['description'])) {
            continue;
        }
        echo html_writer::start_tag('details', ['class' => 'pgosce-section-card', 'open' => 'open']);
        echo html_writer::start_tag('summary');
        echo html_writer::start_div('pgosce-section-title');
        echo html_writer::span($sectionnumber, 'pgosce-section-number');
        echo html_writer::span(format_string($section['name']), 'pgosce-section-name');
        echo html_writer::end_div();
        echo html_writer::end_tag('summary');
        if (!empty($section['description'])) {
            echo html_writer::div(
                pgosce_format_editor_content($context, PGOSCE_FILEAREA_SECTION, $section['id'], $section['description']),
                'pgosce-section-description'
            );
        }
        echo html_writer::end_tag('details');
        $sectionnumber++;
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
} else if (!$rubric && !$canassess) {
    echo $OUTPUT->notification(get_string('nostationinstructions', 'pgosce'), 'info');
}

if ($canmanage || $canassign || $canimport || $canexport) {
    echo html_writer::start_div('pgosce-toolbar');
    if ($canmanage) {
        echo html_writer::link(new moodle_url('/mod/pgosce/manage.php', ['id' => $cm->id]),
            get_string('managerubric', 'pgosce'), ['class' => 'btn btn-primary mr-1']);
        echo ' ';
    }
    if ($canassign) {
        echo html_writer::link(new moodle_url('/mod/pgosce/assign.php', ['id' => $cm->id]),
            get_string('assignassessors', 'pgosce'), ['class' => 'btn btn-secondary mr-1']);
        echo ' ';
    }
    if ($canimport) {
        echo html_writer::link(new moodle_url('/mod/pgosce/gift.php', ['id' => $cm->id]),
            get_string('giftimportexport', 'pgosce'), ['class' => 'btn btn-secondary mr-1']);
    }
    if ($canexport && $rubric) {
        echo html_writer::link(new moodle_url('/mod/pgosce/export.php', ['id' => $cm->id]),
            get_string('exportall', 'pgosce'), ['class' => 'btn btn-secondary']);
    }
    echo html_writer::end_div();

    if ($canexport) {
        $questionlinks = [];
        foreach ($rubric as $section) {
            foreach ($section['questions'] as $question) {
                $url = new moodle_url('/mod/pgosce/export.php', ['id' => $cm->id, 'questionid' => $question['id']]);
                $questionlinks[] = html_writer::link($url, format_string($question['title']));
            }
        }
        if ($questionlinks) {
            echo html_writer::div(get_string('exportquestion', 'pgosce') . ': ' . implode(' | ', $questionlinks),
                'pgosce-panel pgosce-panel-muted');
        }
    }
}

if ($canassess) {
    if (!$hasrubric) {
        echo $OUTPUT->notification(get_string('createrubricfirst', 'pgosce'), 'warning');
        echo $OUTPUT->footer();
        return;
    }

    $students = pgosce_get_students($context);
    if (!$students) {
        echo $OUTPUT->notification(get_string('nostudents', 'pgosce'), 'info');
    } else {
        echo html_writer::start_div('pgosce-student-grid');
        foreach ($students as $student) {
            $grade = pgosce_calculate_student_grade($pgosce, $student->id);
            $attempt = null;
            if (!$hasfullaccess) {
                $attempt = $DB->get_record('pgosce_attempt', [
                    'pgosceid' => $pgosce->id,
                    'userid' => $student->id,
                    'assessorid' => $USER->id,
                ]);
            }
            $locked = !$hasfullaccess && $attempt && $attempt->status == PGOSCE_STATUS_FINAL;
            if (!$hasfullaccess && $attempt) {
                $calc = pgosce_calculate_attempt($attempt);
                $status = $attempt->status == PGOSCE_STATUS_FINAL ? get_string('complete', 'pgosce') :
                    get_string('inprogress', 'pgosce');
                $percentage = format_float($calc['percentage'], 2) . '%';
                $rawgrade = format_float($calc['earned'], 2) . ' / ' . format_float($calc['max'], 2);
            } else {
                $status = $grade ? get_string('complete', 'pgosce') : get_string('notassessed', 'pgosce');
                $percentage = $grade ? format_float($grade->percentage, 2) . '%' : '-';
                $rawgrade = $grade ? format_float($grade->rawgrade, 2) . ' / ' . format_float($pgosce->grade, 2) : '-';
            }
            $actions = '';
            if (!$locked) {
                $actions .= html_writer::link(new moodle_url('/mod/pgosce/assess.php', ['id' => $cm->id, 'userid' => $student->id]),
                    get_string('assess', 'pgosce'), ['class' => 'btn btn-sm btn-primary mr-1']);
                $actions .= html_writer::link(new moodle_url('/mod/pgosce/report.php', ['id' => $cm->id, 'userid' => $student->id]),
                    get_string('report', 'pgosce'), ['class' => 'btn btn-sm btn-secondary']);
            } else {
                $actions = html_writer::span(get_string('finalattemptlocked', 'pgosce'), 'text-muted small');
            }
            echo html_writer::start_div('pgosce-student-card');
            echo html_writer::div(pgosce_format_student_display($student, $pgosce, $context), 'pgosce-student-name');
            echo html_writer::div($status, 'pgosce-badge' . ($grade ? ' pgosce-badge-complete' : ''));
            echo html_writer::div(get_string('percentage', 'pgosce') . ': ' . $percentage, 'mt-2');
            echo html_writer::div(get_string('grade', 'pgosce') . ': ' . $rawgrade, 'text-muted');
            echo html_writer::div($actions, 'pgosce-actions');
            echo html_writer::end_div();
        }
        echo html_writer::end_div();
    }
} else {
    if (has_capability('mod/pgosce:assess', $context) && !pgosce_has_full_access($context)) {
        echo $OUTPUT->notification(get_string('notassignedassessor', 'pgosce'), 'info');
    }
    if (!empty($pgosce->displaystudentreports)) {
        echo html_writer::link(new moodle_url('/mod/pgosce/report.php', ['id' => $cm->id, 'userid' => $USER->id]),
            get_string('viewownreport', 'pgosce'), ['class' => 'btn btn-primary']);
    } else if (!empty($pgosce->showgradesingradebook)) {
        echo $OUTPUT->notification(get_string('gradebookonlyavailable', 'pgosce'), 'info');
    } else if (!$rubric) {
        echo $OUTPUT->notification(get_string('reportnotavailable', 'pgosce'), 'info');
    }
}

echo html_writer::end_div();
echo $OUTPUT->footer();
