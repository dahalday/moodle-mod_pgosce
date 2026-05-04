<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Student report page for PG OSCE.
 *
 * @package    mod_pgosce
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/pgosce/lib.php');

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'pgosce');
$pgosce = $DB->get_record('pgosce', ['id' => $cm->instance], '*', MUST_EXIST);
$student = core_user::get_user($userid, '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pgosce:view', $context);

$viewown = ($userid == $USER->id);
if (!$viewown) {
    require_capability('mod/pgosce:viewreports', $context);
    if (!pgosce_has_full_access($context) && !pgosce_is_assigned_assessor($pgosce, $USER->id)) {
        throw new moodle_exception('notassignedassessor', 'pgosce');
    }
    if (!pgosce_has_full_access($context) && $DB->record_exists('pgosce_attempt', [
            'pgosceid' => $pgosce->id,
            'userid' => $userid,
            'assessorid' => $USER->id,
            'status' => PGOSCE_STATUS_FINAL,
        ])) {
        throw new moodle_exception('finalattemptlocked', 'pgosce');
    }
} else if (empty($pgosce->displaystudentreports)) {
    throw new moodle_exception('reportnotavailable', 'pgosce');
}

$PAGE->set_url('/mod/pgosce/report.php', ['id' => $id, 'userid' => $userid]);
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(get_string('report', 'pgosce'));
$PAGE->set_heading($course->fullname);
$PAGE->requires->css(new moodle_url('/mod/pgosce/styles.css'));

echo $OUTPUT->header();
echo html_writer::start_div('pgosce-shell');
echo html_writer::start_div('pgosce-hero');
echo html_writer::tag('h2', format_string($pgosce->name));
echo html_writer::tag('h3', pgosce_format_student_display($student, $pgosce, $context));
echo html_writer::end_div();

$grade = pgosce_calculate_student_grade($pgosce, $userid);
if ($grade) {
    echo html_writer::tag('p', get_string('grade', 'pgosce') . ': ' . format_float($grade->rawgrade, 2) . ' / ' .
        format_float($pgosce->grade, 2) . ' (' . format_float($grade->percentage, 2) . '%)', ['class' => 'lead']);
} else {
    echo $OUTPUT->notification(get_string('notassessed', 'pgosce'), 'info');
}

$attempts = $DB->get_records('pgosce_attempt', ['pgosceid' => $pgosce->id, 'userid' => $userid], 'timemodified DESC');
$rubric = pgosce_get_rubric($pgosce->id);

foreach ($attempts as $attempt) {
    if ($viewown && $attempt->status != PGOSCE_STATUS_FINAL) {
        continue;
    }
    if (!$viewown && !pgosce_has_full_access($context) && $attempt->assessorid != $USER->id) {
        continue;
    }
    $assessor = core_user::get_user($attempt->assessorid);
    $calc = pgosce_calculate_attempt($attempt);
    echo $OUTPUT->heading(fullname($assessor) . ' - ' .
        ($attempt->status == PGOSCE_STATUS_FINAL ? get_string('complete', 'pgosce') : get_string('inprogress', 'pgosce')), 4);
    echo html_writer::tag('p', format_float($calc['earned'], 2) . ' / ' . format_float($calc['max'], 2) .
        ' (' . format_float($calc['percentage'], 2) . '%)');
    if (!$viewown && pgosce_has_full_access($context)) {
        echo html_writer::link(new moodle_url('/mod/pgosce/assess.php', [
            'id' => $cm->id,
            'userid' => $userid,
            'attemptid' => $attempt->id,
        ]), get_string('editmarks', 'pgosce'), ['class' => 'btn btn-sm btn-primary mb-2']);
    }

    $scores = pgosce_get_scores($attempt->id);
    foreach ($rubric as $section) {
        echo html_writer::tag('h5', format_string($section['name']));
        if (!empty($section['description'])) {
            echo html_writer::div(
                pgosce_format_editor_content($context, PGOSCE_FILEAREA_SECTION, $section['id'], $section['description']),
                'text-muted'
            );
        }
        $table = new html_table();
        $table->head = [get_string('questions', 'pgosce'), get_string('criteria', 'pgosce'), get_string('mark', 'pgosce'), get_string('comment', 'pgosce')];
        foreach ($section['questions'] as $question) {
            $questionlabel = format_string($question['title']);
            if (!empty($question['prompt'])) {
                $questionlabel .= pgosce_format_editor_content($context, PGOSCE_FILEAREA_QUESTION, $question['id'], $question['prompt']);
            }
            foreach ($question['criteria'] as $criterion) {
                $score = isset($scores[$criterion['id']]) ? $scores[$criterion['id']] : null;
                $table->data[] = [
                    $questionlabel,
                    pgosce_format_editor_content(
                        $context,
                        PGOSCE_FILEAREA_CRITERION,
                        $criterion['id'],
                        $criterion['description']
                    ),
                    ($score ? format_float($score->mark, 2) : '0') . ' / ' . format_float($criterion['maxmark'], 2),
                    $score ? format_text($score->comment) : '',
                ];
            }
        }
        echo html_writer::table($table);
    }
    if (!empty($attempt->generalcomment)) {
        echo html_writer::tag('p', format_text($attempt->generalcomment));
    }
}

echo html_writer::link(new moodle_url('/mod/pgosce/view.php', ['id' => $cm->id]), get_string('backtoactivity', 'pgosce'), ['class' => 'btn btn-secondary']);
echo html_writer::end_div();
echo $OUTPUT->footer();
