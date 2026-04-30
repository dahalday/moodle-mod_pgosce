<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Course index page for PG OSCE activities.
 *
 * @package    mod_pgosce
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/pgosce/lib.php');

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$PAGE->set_url('/mod/pgosce/index.php', ['id' => $id]);
$PAGE->set_title(get_string('modulenameplural', 'pgosce'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'pgosce'));

$instances = get_all_instances_in_course('pgosce', $course);
if (!$instances) {
    echo $OUTPUT->notification(get_string('noinstances', 'error'), 'info');
} else {
    $table = new html_table();
    $table->head = [get_string('name'), get_string('grade')];
    foreach ($instances as $instance) {
        $link = new moodle_url('/mod/pgosce/view.php', ['id' => $instance->coursemodule]);
        $table->data[] = [html_writer::link($link, format_string($instance->name)), format_float($instance->grade, 2)];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
