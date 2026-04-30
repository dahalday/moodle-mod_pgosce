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

$PAGE->set_url('/mod/pgosce/view.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(format_string($pgosce->name));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($pgosce->name));
echo format_module_intro('pgosce', $pgosce, $cm->id);

$canassess = pgosce_can_assess_station($pgosce, $context);
$canmanage = has_capability('mod/pgosce:manage', $context);
$canassign = has_capability('mod/pgosce:assignassessors', $context);
$canimport = has_capability('mod/pgosce:import', $context);
$canexport = has_capability('mod/pgosce:export', $context);
$hasrubric = pgosce_get_total_maxmark($pgosce->id) > 0;
$rubric = pgosce_get_rubric($pgosce->id);
$showinstructions = $rubric && ($canassess || $canmanage || !empty($pgosce->showstudentinstructions));

if ($showinstructions) {
    echo $OUTPUT->heading(get_string('candidateinstructions', 'pgosce'), 3);
    echo html_writer::start_div('pgosce-candidate-instructions mb-3');
    foreach ($rubric as $section) {
        if (empty($section['name']) && empty($section['description'])) {
            continue;
        }
        echo html_writer::start_div('pgosce-candidate-section mb-3');
        if (!empty($section['name'])) {
            echo $OUTPUT->heading(format_string($section['name']), 4);
        }
        if (!empty($section['description'])) {
            echo html_writer::div(
                pgosce_format_editor_content($context, PGOSCE_FILEAREA_SECTION, $section['id'], $section['description']),
                'pgosce-candidate-section-description'
            );
        }
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
} else if (!$rubric && !$canassess) {
    echo $OUTPUT->notification(get_string('nostationinstructions', 'pgosce'), 'info');
}

if ($canmanage || $canassign || $canimport || $canexport) {
    echo html_writer::start_div('mb-3');
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
    if ($canexport) {
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
            echo html_writer::div(get_string('exportquestion', 'pgosce') . ': ' . implode(' | ', $questionlinks), 'mb-3');
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
        $table = new html_table();
        $table->head = [
            get_string('student', 'pgosce'),
            get_string('status', 'pgosce'),
            get_string('percentage', 'pgosce'),
            get_string('grade', 'pgosce'),
            get_string('actions', 'pgosce'),
        ];
        foreach ($students as $student) {
            $grade = pgosce_calculate_student_grade($pgosce, $student->id);
            $status = $grade ? get_string('complete', 'pgosce') : get_string('notassessed', 'pgosce');
            $percentage = $grade ? format_float($grade->percentage, 2) . '%' : '-';
            $rawgrade = $grade ? format_float($grade->rawgrade, 2) . ' / ' . format_float($pgosce->grade, 2) : '-';
            $actions = html_writer::link(new moodle_url('/mod/pgosce/assess.php', ['id' => $cm->id, 'userid' => $student->id]),
                get_string('assess', 'pgosce'), ['class' => 'btn btn-sm btn-primary mr-1']);
            $actions .= html_writer::link(new moodle_url('/mod/pgosce/report.php', ['id' => $cm->id, 'userid' => $student->id]),
                get_string('report', 'pgosce'), ['class' => 'btn btn-sm btn-secondary']);
            $table->data[] = [fullname($student), $status, $percentage, $rawgrade, $actions];
        }
        echo html_writer::table($table);
    }
} else {
    if (has_capability('mod/pgosce:assess', $context) && !pgosce_has_full_access($context)) {
        echo $OUTPUT->notification(get_string('notassignedassessor', 'pgosce'), 'info');
    }
    if (!empty($pgosce->displaystudentreports)) {
        echo html_writer::link(new moodle_url('/mod/pgosce/report.php', ['id' => $cm->id, 'userid' => $USER->id]),
            get_string('viewownreport', 'pgosce'), ['class' => 'btn btn-primary']);
    } else if (!$rubric) {
        echo $OUTPUT->notification(get_string('reportnotavailable', 'pgosce'), 'info');
    }
}

echo $OUTPUT->footer();
