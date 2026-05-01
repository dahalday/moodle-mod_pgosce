<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Assign PG OSCE assessors.
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
require_capability('mod/pgosce:assignassessors', $context);

$PAGE->set_url('/mod/pgosce/assign.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(get_string('assignassessors', 'pgosce'));
$PAGE->set_heading($course->fullname);
$PAGE->requires->css(new moodle_url('/mod/pgosce/styles.css'));

$assessors = pgosce_get_assignable_assessors($context);

if (data_submitted() && confirm_sesskey()) {
    $courseassessors = optional_param_array('courseassessors', [], PARAM_INT);
    $stationassessors = optional_param_array('stationassessors', [], PARAM_INT);
    $validuserids = array_keys($assessors);

    $courseassessors = array_values(array_intersect($courseassessors, $validuserids));
    $stationassessors = array_values(array_intersect($stationassessors, $validuserids));

    pgosce_save_assessor_assignments($course->id, 0, $courseassessors);
    pgosce_save_assessor_assignments($course->id, $pgosce->id, $stationassessors);

    redirect($PAGE->url, get_string('assignassessorssaved', 'pgosce'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

$courseassigned = $DB->get_records_menu('pgosce_assessor', ['course' => $course->id, 'pgosceid' => 0], '', 'userid, id');
$stationassigned = $DB->get_records_menu('pgosce_assessor', ['course' => $course->id, 'pgosceid' => $pgosce->id], '',
    'userid, id');

echo $OUTPUT->header();
echo html_writer::start_div('pgosce-shell');
echo html_writer::start_div('pgosce-hero');
echo html_writer::tag('h2', get_string('assignassessors', 'pgosce'));
echo html_writer::tag('p', get_string('assignassessorsintro', 'pgosce'));
echo html_writer::end_div();

if (!$assessors) {
    echo $OUTPUT->notification(get_string('noassignableassessors', 'pgosce'), 'info');
    echo html_writer::link(new moodle_url('/mod/pgosce/view.php', ['id' => $cm->id]), get_string('backtoactivity', 'pgosce'),
        ['class' => 'btn btn-secondary']);
    echo html_writer::end_div();
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

$table = new html_table();
$table->head = [
    get_string('assessor', 'pgosce'),
    get_string('coursedefaultassessors', 'pgosce'),
    get_string('stationassessors', 'pgosce'),
];

foreach ($assessors as $assessor) {
    $table->data[] = [
        fullname($assessor) . html_writer::div(s($assessor->email), 'text-muted small'),
        html_writer::checkbox('courseassessors[]', $assessor->id, isset($courseassigned[$assessor->id]), '',
            ['aria-label' => get_string('coursedefaultassessors', 'pgosce') . ': ' . fullname($assessor)]),
        html_writer::checkbox('stationassessors[]', $assessor->id, isset($stationassigned[$assessor->id]), '',
            ['aria-label' => get_string('stationassessors', 'pgosce') . ': ' . fullname($assessor)]),
    ];
}

echo html_writer::start_div('pgosce-panel');
echo html_writer::table($table);
echo html_writer::end_div();
echo html_writer::start_div('pgosce-actions');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'class' => 'btn btn-primary',
    'value' => get_string('save', 'pgosce'),
]);
echo ' ';
echo html_writer::link(new moodle_url('/mod/pgosce/view.php', ['id' => $cm->id]), get_string('backtoactivity', 'pgosce'),
    ['class' => 'btn btn-secondary']);
echo html_writer::end_div();
echo html_writer::end_tag('form');
echo html_writer::end_div();

echo $OUTPUT->footer();
